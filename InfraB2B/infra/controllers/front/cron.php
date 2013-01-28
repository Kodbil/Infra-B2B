<?php
@include_once(dirname(__FILE__).'/../../helper/xmlrpc.php');
@include_once(dirname(__FILE__).'/../../helper/csv.php');


class InfraCronModuleFrontController extends ModuleFrontController
{
	public function init()
	{

            $data = $this->orderUpdate();
            if(!empty($data))
            {
                $url = 'upload/'.Tools::passwdGen().'.csv';
                $this->write_file( $url, array_to_csv($data),'a+');
                print_r($this->addOrderRequest('http://'.Tools::getHttpHost().'/'.basename(__PS_BASE_URI__).'/'.$url));
                
            }
            $this->cron();

                        
	}

	public function initContent()
	{
            
	}
        public function cron()
        {

            
            $request = array(
                        array('request' =>'stock_update', 'period' => Configuration::get('INFRA_PRODUCT_PERIOD'), 'timeout' => Configuration::get('INFRA_UPDATE_TIMEOUT')),
                        array('request' =>'stock_qty_update', 'period' => Configuration::get('INFRA_STOCK_PERIOD'), 'timeout' => Configuration::get('INFRA_UPDATE_TIMEOUT')),
                        array('request' =>'stock_price_update', 'period' => Configuration::get('INFRA_PRICE_PERIOD'), 'timeout' => Configuration::get('INFRA_UPDATE_TIMEOUT')),
                        array('request' =>'stock_price_list_update', 'period' => Configuration::get('INFRA_PRICE_PERIOD'), 'timeout' => Configuration::get('INFRA_UPDATE_TIMEOUT')),
                        array('request' =>'customer_update', 'period' => Configuration::get('INFRA_CUSTOMER_PERIOD'), 'timeout' => Configuration::get('INFRA_UPDATE_TIMEOUT')));
            $data = $this->orderUpdate();
       
            $this->addRequest(json_encode($request));
            $resultRes = $this->getResponse();
            if($resultRes['status'])
            {
                
                $array = csv_to_array(file_get_contents($resultRes['url']));
                $this->createTableAndFill($resultRes['request'], $array);
                switch ($resultRes['request']) {
                    case 'customer_update':
                        $this->updateCustomer();

                        break;
                    case 'stock_price_list_update':
                        $this->updateCustomerGroup();

                        break;
                    case 'stock_qty_update':
                        $this->stockUpdate();

                        break;
                    case 'stock_update':
                        $this->addFeatures();
                        $this->updateProduct();
                        
                        break;
                    case 'stock_price_update':
                        $this->updateProductPrice();

                        break;

                    default:
                        break;
                }
            }
            
            
        }
        
        private function addRequest($param)
        {
            $xmlrpc = new Xmlrpc;
            
            $server_url = Configuration::get('INFRA_SERVER_NAME');
            $xmlrpc->server($server_url, 80);
            $xmlrpc->method('addRequest');
            
            $request = array(
             array(
                                            
                   array(
                        'username'  =>array(Configuration::get('INFRA_SERVER_USER'),'string'),
                        'password'  =>array(Configuration::get('INFRA_SERVER_PASS'),'string'),
                        'api_key'   =>array(Configuration::get('INFRA_SERVER_API'),'string'),
                        'req'       =>array($param)
                            ),'struct'


                   ),'struct'
             );
            
            $xmlrpc->request($request);
            
            if ( ! $xmlrpc->send_request())
            {
                $result = $xmlrpc->display_error();
            }
            else
            {
                $result = $xmlrpc->display_response();
            }
            
            return $result;
   
        }
        
        private function addOrderRequest($url)
        {
            $xmlrpc = new Xmlrpc;
            
            $server_url = Configuration::get('INFRA_SERVER_NAME');
            $xmlrpc->server($server_url, 80);
            $xmlrpc->method('addOrderRequest');
            
            $request = array(
             array(
                                            
                   array(
                        'username'  =>array(Configuration::get('INFRA_SERVER_USER'),'string'),
                        'password'  =>array(Configuration::get('INFRA_SERVER_PASS'),'string'),
                        'api_key'   =>array(Configuration::get('INFRA_SERVER_API'),'string'),
                        'url'       =>array($url,'string')
                            ),'struct'


                   ),'struct'
             );
            
            $xmlrpc->request($request);
            
            if ( ! $xmlrpc->send_request())
            {
                $result = $xmlrpc->display_error();
            }
            else
            {
                $result = $xmlrpc->display_response();
            }
            
            return $result;
   
        }
        
        private function getResponse()
        {
            $xmlrpc = new Xmlrpc;
            
            $server_url = Configuration::get('INFRA_SERVER_NAME');
            $xmlrpc->server($server_url, 80);
            $xmlrpc->method('getResponse');
            
            
            $request = array(
             array(
                                            
                   array(
                        'username'  =>array(Configuration::get('INFRA_SERVER_USER'),'string'),
                        'password'  =>array(Configuration::get('INFRA_SERVER_PASS'),'string'),
                        'api_key'   =>array(Configuration::get('INFRA_SERVER_API'),'string'),
                            ),'struct'


                   ),'struct'
             );
            
            $xmlrpc->request($request);
            
            if ( ! $xmlrpc->send_request())
            {
                $result = $xmlrpc->display_error();
            }
            else
            {
                $result = $xmlrpc->display_response();
            }
            return $result;
            
        }
        
        
        private function createTableAndFill($table, $array)
        {
            
            if(!is_array($array))
                return false;
            
            $db = Db::getInstance();
            $field = array();
            switch($table)
            {
                case 'stock_update':
                    $field = array('sku', 'name' ,'currency', 'qty', 'tax_retail', 'tax_wholesale');
                    break;
                case 'customer_update':
                    $field = array('code', 'name', 'price_list');
                    break;
                case 'stock_qty_update':
                    $field = array('sku', 'qty');
                    break;
                case 'stock_price_update':
                    $field = array('sku', 'list', 'price', 'currency');
                    break;
                case 'stock_price_list_update':
                    $field = array('list', 'define');
                    break;
                
            }
            
            $query = "SELECT * FROM information_schema.tables WHERE table_name = '".$table."'";
            $result = $db->ExecuteS($query);
            if(!empty($result))
            {
                $query = "DROP TABLE ".$table;
                $result = $db->query($query);
                
            }

            $query = "CREATE TABLE `".$table."` ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,";

            foreach($field as $value)
            {
                $query .="`".$value."` VARCHAR (255),";
            }
            
            $query = substr($query, 0, strlen($query)-1).") ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
            $db = Db::getInstance(); 
            $db->Execute($query);
            
            $query = "INSERT INTO ".$table." (".implode(', ', $field).") VALUES ";
            foreach ($array as $key => $value)
            {
                if(count($value) < 2)
                    break;
                
                $values = iconv("ISO-8859-9", "UTF-8", implode("\", \"", $value));
                $queries .= "( \"".$values."\" ),"; 
                $queries = substr($queries, 0, strlen($queries)-1);
                $db->query($query.$queries);
                $queries ='';
                
            }
            
        }
        private function GenerateUrl ($s) 
        { 
            $from = explode (',', "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,(,),[,],'"); 
            $to = explode (',', 'c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,,,,,,'); 
            $s = preg_replace ('~[^\w\d]+~', '-', str_replace ($from, $to, trim ($s)));
            return strtolower (preg_replace ('/^-/', '', preg_replace ('/-$/', '', $s))); 
        }
        
        private function updateCustomer()
        {
            

            $allCustomer =array();
            foreach (Customer::getCustomers() as $value) {
                $cus = new Customer($value['id_customer']);
                array_push($allCustomer, array('id' => $cus->id, 'company' => $cus->company));
                
            }
            
            $query = "SELECT * FROM customer_update JOIN stock_price_list_update ON customer_update.price_list = stock_price_list_update.list";
            $result = Db::getInstance()->executeS($query);
            foreach($result as $value)
            {
                $customerObject = new Customer();
            
                if($this->deep_in_array($value['code'], $allCustomer))
                    continue;
                
                foreach(Group::getGroups(1) as $val)
                {
                    if($val['name'] == $value['define'])

                        $customerObject->id_default_group = $val['id_group'];
                }
                $customerObject->active = 1;
                $customerObject->firstname= 'Demo';
                $customerObject->lastname = 'Demo';
                $customerObject->email = $value['code'].'@demo.com';
                $password = Tools::passwdGen();
                $customerObject->passwd = md5(_COOKIE_KEY_.$password);
                $customerObject->company = $value['code'];
                $customerObject->save();
                mail(Configuration::get('INFRA_ADMIN_MAIL'),"New User Password", "Mail :". $value['code']."@demo.com   Password: $password",'From : Prestashop');
                
            }
            
            
        }
        
        private function updateCustomerGroup()
        {
            
            $query = "SELECT * FROM stock_price_list_update";
            $result = Db::getInstance()->executeS($query);
            $allGroups = Group::getGroups(1);
            $query = "SELECT id_module FROM module";
            $module = Db::getInstance()->executeS($query);
            foreach($result as $value)
            {
                if($this->deep_in_array($value['define'], $allGroups))
                    continue;
                
                $group = new Group();

                $group->name = $value['define'];
                $group->price_display_method = 0;

                $group->save();
                $modules = array();
                foreach ($module as $mod) {
                    $modules[] = $mod['id_module'];
                    
                }
                Group::addModulesRestrictions($group->id, $modules);
                
            }
            
            
        }

        private function deep_in_array($value, $array) 
        { 
            foreach($array as $item) { 
                if(!is_array($item)) { 
                    if ($item == $value) return true; 
                    else continue; 
                } 

                if(in_array($value, $item, true)) return true; 
                else if($this->deep_in_array($value, $item)) return true; 
            } 
            return false; 
        }
        
        private function addFeatures()
        {
            
            $allFeatures = Feature::getFeatures(1);
            if(!$this->deep_in_array('StokKodu', $allFeatures))
            {
                $features = new Feature();
                $features->name = 'StokKodu';
                $features->save();
                return false;
            }
            return true;
        }
        
        private function updateProduct()
        {
            $language = Language::getLanguages(false);
            $fetureId = $this->findFeatureId('StokKodu');
            $allFeatureValue = $this->getFeatureValues($fetureId);
            echo memory_get_usage();
            
                $query = "SELECT * FROM stock_update";
                $result = Db::getInstance()->executeS($query);
                foreach($result as $value)
                {
                    if(!Validate::isCarrierName($value['name']))
                        continue;

                    if($this->deep_in_array($value['sku'], $allFeatureValue))
                        continue;

                    $fetureValue = new FeatureValue();

                    $fetureValue->id_feature = $fetureId;
                    $fetureValue->value = $value['sku'];
                    $fetureValue->save();
                    $id_feature_value = $fetureValue->id;
                    $newProduct = new Product();
                    $name = array();
                    foreach($language as $val)
                    {
                           $name[$val['id_lang']] = $value['name'];
                    }
                    $link_rewrite = array();
                    foreach($language as $val)
                    {
                           $link_rewrite[$val['id_lang']] = $this->GenerateUrl($value['name']);
                    }
                    $newProduct->active = 1;
                    $newProduct->name = $name;
                    $newProduct->tax_rate = 0;
                    $newProduct->link_rewrite = $link_rewrite;
                    $newProduct->quantity = $value['qty'];
                    $newProduct->id_tax_rules_group = $this->getTaxRulesGroup($value['tax_wholesale']);
                    $newProduct->save();

                    $id_product = $newProduct->id;

                    Product::addFeatureProductImport($id_product, $this->findFeatureId('StokKodu'), $id_feature_value);

                }
            
           
            
            
        }
        
        private function findFeatureId($name = 'StokKodu') {
            
            foreach(Feature::getFeatures(1) as $value)
            {
                if($name == $value['name'])
                    return $value['id_feature'];
            }
            return false;
        }
        
        private function getFeatureValues($id)
        {
            
            $result = array();
            foreach(FeatureValue::getFeatureValues($id) as $value)
            {
                $val = FeatureValue::getFeatureValueLang($value['id_feature_value']);
  
                array_push($result, $val[0]['value']);

            }
           
            return $result;
        }
        
        private function getProductIdByFeature($feature)
	{
		if (!Feature::isFeatureActive())
			return false;

		if (empty($feature))
			return false;

		$result = Db::getInstance()->executeS("
                            SELECT `id_product`
                            FROM `"._DB_PREFIX_."feature_value_lang` fvl
                            LEFT JOIN `"._DB_PREFIX_."feature_product` fp
                                    ON fvl.`id_feature_value` = fp.`id_feature_value`
                            WHERE fvl.`value` = '${feature}'
                            LIMIT 0,1
                        ");
               return isset($result[0]['id_product']) ? $result[0]['id_product'] : false ;
	}
        
        private function getGroupIdByName($group)
        {
            if (empty($group))
			return false;

		$result = Db::getInstance()->executeS("
                            SELECT `id_group`
                            FROM `"._DB_PREFIX_."group_lang` gl
                            WHERE gl.`name` = '${group}'
                            LIMIT 0,1
                        ");
               return isset($result[0]['id_group']) ? $result[0]['id_group'] : false ;
        }
        
        private function updateProductPrice()
        {
            $query = "SELECT sku FROM stock_price_update group by sku";
            $resultProduct = Db::getInstance()->executeS($query);
            foreach($resultProduct as $sku)
            {
                $stockCode = $sku['sku'];
                $query = "SELECT * FROM stock_price_update JOIN stock_price_list_update ON stock_price_update.list = stock_price_list_update.list WHERE sku ='$stockCode'";
                $result = Db::getInstance()->executeS($query);
                foreach($result as $value)
                {

                    $id_product = $this->getProductIdByFeature($value['sku']);
                    if(!$id_product)
                        continue;
                    $id_group = $this->getGroupIdByName($value['define']);
                    $productSpecificPrice = SpecificPrice::getByProductId($id_product);
                    $priceId = $this->getSpecificPriceId($id_product, $id_group);
                    if($this->checkSpecificPrice($priceId, $productSpecificPrice))
                    {
                        $price = new SpecificPrice($priceId);
                        $price->price = $value['price'];
                        $price->save();
                    }
                    else 
                    {
                        $price = new SpecificPrice();
                        $price->id_product = $id_product;
                        $price->id_shop = 0;
                        $price->id_customer = 0;
                        $price->id_country = 0;
                        $price->from_quantity = 1;
                        $price->reduction = 0;
                        $price->reduction_type = 'amount';
                        $price->id_group =$id_group;
                        $price->from = date('Y-m-d h:i:s');
                        $price->to = '2100-01-01 00:00:00';
                        $price->id_currency = 0;
                        $price->price = $value['price'];
                        $price->save();
                    }
                    

                }
                
               
                
            }
            
        }
        
        private function checkSpecificPrice($priceId, $productSpecificPrice)
        {
            
            foreach ($productSpecificPrice as $value) {
                if($value['id_specific_price'] == $priceId)
                    return true;
            }
            return false;
        }
        
        private function stockUpdate()
        {
            $query = "SELECT * FROM stock_qty_update";
            $result = Db::getInstance()->executeS($query);
            foreach($result as $value)
            {
                
                $product = new Product($this->getProductIdByFeature($value['sku']));
                if(empty($product->id))
                    continue;
                
                $product->quantity = $value['qty'];
                $product->save();
               
            }
            
        }
        
        private function getSpecificPriceId($id_product, $id_group)
        {
            
            $query = "SELECT * FROM specific_price WHERE id_product = $id_product AND id_group = $id_group LIMIT 0,1";
            $result = Db::getInstance()->executeS($query);
            
            return isset($result[0]['id_specific_price']) ? $result[0]['id_specific_price'] : false ;
        }
        
        private function getCurrencyIsoCodeNum($id)
        {
            switch ($id)
            {
                case 0:
                    return 949;
                case 1:
                    return 840;
                case 2:
                    return 978;
                       
            }
        }
        private function getTaxRulesGroup($rate)
        {
            foreach (Tax::getTaxes() as $value) {
                if($value['rate'] == $rate)
                    return $value['id_tax'];
            }
            return '';
        }
        
        private function orderUpdate()
        {
           
           $orderIds = Order::getOrdersIdByDate(Configuration::get('LAST_ORDER_UPDATE_TIME'), date('Y-m-d h:i:s'));
           Configuration::updateValue('LAST_ORDER_UPDATE_TIME', date('Y-m-d h:i:s'));
           $orders = array();
           foreach ($orderIds as $idOrder) {
               $order = new Order($idOrder);
               $customer = $this->getCustomerCompany($order->id_customer);
               
               if($customer == "")
                   continue;
               
               foreach(OrderDetail::getList($idOrder) as $value)
               {
                   
                   if(!$this->getStockCode($value['product_id']))
                        continue;
                   
                   array_push($orders, array( 'order_id' => $value['id_order'],
                                              'qty'      => $value['product_quantity'],
                                              'price'    => $value['product_price'],
                                              'price_tax_incl' => $value['unit_price_tax_incl'],
                                              'sku'      => $this->getStockCode($value['product_id']),
                                              'customer' => $customer ));
                   
               }
                       
               
           }
           
           return $orders; 
            
            
        }
        
        private function getStockCode($id)
        {
            
            $query = "SELECT value AS sku FROM feature_value_lang vl
                      JOIN feature_product fp ON vl.id_feature_value = fp.id_feature_value
                      where fp.id_product = $id LIMIT 0,1";
            $result = Db::getInstance()->executeS($query);
            
            return isset($result[0]) ? $result[0]['sku'] : false ;
        }
        
        private function getCustomerCompany($id)
        {
            
            $customer = new Customer($id);
            
            return $customer->company;
            
        }
        
        function write_file($path, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
	{
            if ( ! $fp = @fopen($path, $mode))
            {
                  return false;
            }

            flock($fp, LOCK_EX);
            fwrite($fp, $data);
            flock($fp, LOCK_UN);
            fclose($fp);

            return true;
	}
        
        private function getTableRowCount($table)
        {
            
            $query = "SELECT table_rows FROM INFORMATION_SCHEMA.TABLES where table_name ='$table'";
            $count = Db::getInstance()->executeS($query);
            
            return isset($count[0]) ? $count[0]['table_rows'] : false;
        }
        
}
