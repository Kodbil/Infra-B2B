<?php
 
if (!defined('_PS_VERSION_'))
	exit;

class Infra extends Module
{
	public function __construct()
	{
		$this->name = 'infra';
		$this->tab = 'back_office_features';
		$this->version = '1.0';
		$this->author = 'İnfra Teknoloji';
                
                $this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Infra B2B');
		$this->description = $this->l('Infra Teknoloji Entegrasyon Modülü.');
	}

	public function install()
	{
		if (!parent::install() ||
                        !Configuration::updateValue('INFRA_STOCK_PERIOD', '') ||
                        !Configuration::updateValue('INFRA_PRODUCT_PERIOD', '') ||
                        !Configuration::updateValue('INFRA_CUSTOMER_PERIOD', '') ||
                        !Configuration::updateValue('INFRA_PRICE_PERIOD', '') ||
                        !Configuration::updateValue('INFRA_UPDATE_TIMEOUT', '') ||
                        !Configuration::updateValue('INFRA_ORDER_PERIOD', '') ||
                        !Configuration::updateValue('INFRA_SERVER_NAME', '') ||
                        !Configuration::updateValue('INFRA_SERVER_API', '') ||
                        !Configuration::updateValue('INFRA_SERVER_USER', '') ||
                        !Configuration::updateValue('INFRA_SERVER_PASS', '') ||
			!Configuration::updateValue('INFRA_ADMIN_MAIL', '') ||
                        !Configuration::updateValue('LAST_ORDER_UPDATE_TIME', date('Y-m-d h:i:s')))
                        return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
		return true;
	}
        
        public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitHomeFeatured'))
		{
			$server   = Tools::getValue('server');
                        $apikey   = Tools::getValue('apikey');
                        $user     = Tools::getValue('username');
                        $pass     = Tools::getValue('password');
                        $stock    = Tools::getValue('stock');
                        $mail     = Tools::getValue('mail');
                        $product  = Tools::getValue('product');
                        $price    = Tools::getValue('price');
                        $customer = Tools::getValue('customer');
                        $order    = Tools::getValue('order');
                        $timeout  = Tools::getValue('timeout');

                        if (!Validate::isString($server))
				$errors[] = $this->l("This field must not be null");

			else{
                                Configuration::updateValue('INFRA_SERVER_NAME', $server);
                                Configuration::updateValue('INFRA_SERVER_API', $apikey);
                                Configuration::updateValue('INFRA_SERVER_USER', $user);
                                Configuration::updateValue('INFRA_SERVER_PASS', $pass);
                                Configuration::updateValue('INFRA_ADMIN_MAIL', $mail);
                                Configuration::updateValue('INFRA_PRODUCT_PERIOD', $product);
                                Configuration::updateValue('INFRA_STOCK_PERIOD', $stock);
                                Configuration::updateValue('INFRA_PRICE_PERIOD', $price);
                                Configuration::updateValue('INFRA_CUSTOMER_PERIOD', $customer);
                                Configuration::updateValue('INFRA_ORDER_PERIOD', $order);
                                Configuration::updateValue('INFRA_UPDATE_TIMEOUT', $timeout);

                               

				
                        }
			if (isset($errors) AND sizeof($errors))
				$output .= $this->displayError(implode('<br />', $errors));
			else
				$output .= $this->displayConfirmation($this->l('Ayarlar Kaydedildi'));
		}
                
               
		return $output.$this->displayForm();
	}
        private function createSelectList($val = null)
        {
            $list = array(
                           array('value' => 300, 'label' => '5 Dakikada bir' ),
                           array('value' => 1800, 'label' => 'Her yarım saatte' ),
                           array('value' => 3600, 'label' => 'Her saat' ),
                           array('value' => 43200, 'label' => 'Günde iki defa' ),
                           array('value' => 86400, 'label' => 'Her gün' ),
                           array('value' => 302400, 'label' => 'Haftada iki defa' ),
                           array('value' => 604800, 'label' => 'Her hafata' ),
                           array('value' => 2592000, 'label' => 'Ayda bir' ));
            $result = '';
            
            foreach ($list as $value) {
                
                if($value['value'] == $val)
                {
                    $result .= "<option value=\"".$value['value']."\" selected >".$value['label']."</option>";
                }
                else {
                    $result .= "<option value=\"".$value['value']."\">".$value['label']."</option>";
                }
            }
            return $result;
        }

	public function displayForm()
	{
		
            
            
            
            $output = '
		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
			<fieldset class="width4"><legend><img src="'.$this->_path.'settings.png" alt="" title="" />'.$this->l('Ayarlar').'</legend>
				<p>'.$this->l('Web Servis bilgileri.').'</p><br />
                                <label>'.$this->l('Sever Name').'</label>
                                <div class="margin-form">
					<input type="text" size="50" name="server" value="'.Tools::safeOutput(Tools::getValue('server', (Configuration::get('INFRA_SERVER_NAME')))).'" />

				</div>
                                <label>'.$this->l('Api Key').'</label>
                                <div class="margin-form">
					<input type="text" size="50" name="apikey" value="'.Tools::safeOutput(Tools::getValue('apikey', (Configuration::get('INFRA_SERVER_API')))).'" />

				</div>
                                <label>'.$this->l('User Name').'</label>
                                <div class="margin-form">
					<input type="text" size="50" name="username" value="'.Tools::safeOutput(Tools::getValue('username', (Configuration::get('INFRA_SERVER_USER')))).'" />

				</div>
                                <label>'.$this->l('Password').'</label>
                                <div class="margin-form">
					<input type="text" size="50" name="password" value="'.Tools::safeOutput(Tools::getValue('password', (Configuration::get('INFRA_SERVER_PASS')))).'" />

				</div>
                                <br>
                                <p>'.$this->l('Kullanıcı oluşturulurken şifreler bu adrese gönderilecek.').'</p><br />
                                <label>'.$this->l('e-mail').'</label>
                                <div class="margin-form">
					<input type="text" size="50" name="mail" value="'.Tools::safeOutput(Tools::getValue('mail', (Configuration::get('INFRA_ADMIN_MAIL')))).'" />

				</div>
				<center><input type="submit" name="submitHomeFeatured" value="'.$this->l('Kaydet').'" class="button" /></center>
			</fieldset>
		';
                
                $output .= '
                <br>

		
			<fieldset class="width4" ><legend><img src="'.$this->_path.'update.png" alt="" title="" />'.$this->l('Stok Güncelle').'</legend>
				<p>'.$this->l('Güncelleme zaman aralıkları.').'</p><br />
                                <label>'.$this->l('Ürün Güncelleme').'</label>
                                <div class="margin-form">
                                        <select name="product">
                                            '.$this->createSelectList(Tools::safeOutput(Tools::getValue('product', (Configuration::get('INFRA_PRODUCT_PERIOD'))))).'
                                         </select>                                
                                </div>
                                <label>'.$this->l('Stok Güncelleme').'</label>
                                <div class="margin-form">
                                        <select name="stock">
                                            '.$this->createSelectList(Tools::safeOutput(Tools::getValue('stock', (Configuration::get('INFRA_STOCK_PERIOD'))))).'
                                         </select> 
                                </div>
                                
                                <label>'.$this->l('Fiyat Güncelleme').'</label>
                                <div class="margin-form">
                                        <select name="price">
                                            '.$this->createSelectList(Tools::safeOutput(Tools::getValue('price', (Configuration::get('INFRA_PRICE_PERIOD'))))).'
                                         </select>                                 
                                </div>
                                
                                <label>'.$this->l('Cari Hesaplar Güncelleme').'</label>
                                <div class="margin-form">
                                        <select name="customer">
                                            '.$this->createSelectList(Tools::safeOutput(Tools::getValue('customer', (Configuration::get('INFRA_CUSTOMER_PERIOD'))))).'
                                         </select> 
				</div>
                                
                                <label>'.$this->l('Sipariş Güncelleme').'</label>
                                <div class="margin-form">
					<select name="order">
                                            '.$this->createSelectList(Tools::safeOutput(Tools::getValue('order', (Configuration::get('INFRA_ORDER_PERIOD'))))).'
                                         </select> 
				</div>
                                <label>'.$this->l('Timeout').'</label>
                                <div class="margin-form">
					<input type="text" size="10" name="timeout" value="'.Tools::safeOutput(Tools::getValue('timeout', (Configuration::get('INFRA_UPDATE_TIMEOUT')))).'" />

				</div>
				<center><input type="submit" name="submitHomeFeatured" value="'.$this->l('Kaydet').'" class="button" /></center>
			</fieldset>
		</form>';

		return $output;
	}
        
                        
}
?>

