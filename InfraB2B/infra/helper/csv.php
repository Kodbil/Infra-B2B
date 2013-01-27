<?php

if ( ! function_exists('array_to_csv'))
{
	function array_to_csv($array)
	{

		ob_start();
		$f = fopen('php://output', 'w') or show_error("Can't open php://output");
		
		$n = 0;		
		foreach ($array as $line)
		{
			$n++;
			if ( ! fputcsv($f, $line))
			{
				show_error("Can't write line $n: $line");
			}
		}
		fclose($f) or show_error("Can't close php://output");
		$str = ob_get_contents();
		ob_end_clean();
		return $str;	
			
	}
}
// ------------------------------------------------------------------------


if ( ! function_exists('csv_to_array'))
{
	function csv_to_array($fileContent, $escape = '\\', $enclosure = '"', $delimiter = ',')
        {
            $lines = array();
            $fields = array();

            if($escape == $enclosure)
            {
                    $escape = '\\';
                    $fileContent = str_replace(array('\\',$enclosure.$enclosure,"\r\n","\r"),
                                            array('\\\\',$escape.$enclosure,"\\n","\\n"),$fileContent);
            }
            else
                    $fileContent = str_replace(array("\r\n","\r"),array("\\n","\\n"),$fileContent);

            $nb = strlen($fileContent);
            $field = '';
            $inEnclosure = false;
            $previous = '';

            for($i = 0;$i<$nb; $i++)
            {
                    $c = $fileContent[$i];
                    if($c === $enclosure)
                    {
                            if($previous !== $escape)
                                    $inEnclosure ^= true;
                            else
                                    $field .= $enclosure;
                    }
                    else if($c === $escape)
                    {
                            $next = $fileContent[$i+1];
                            if($next != $enclosure && $next != $escape)
                                    $field .= $escape;
                    }
                    else if($c === $delimiter)
                    {
                            if($inEnclosure)
                                    $field .= $delimiter;
                            else
                            {
                                    //end of the field
                                    $fields[] = $field;
                                    $field = '';
                            }
                    }
                    else if($c === "\n")
                    {
                            $fields[] = $field;
                            $field = '';
                            $lines[] = $fields;
                            $fields = array();
                    }
                    else
                            $field .= $c;
                    $previous = $c;
            }

            if($field !== '')
            {
                    $fields[] = $field;
                    $lines[] = $fields;
            }
            return $lines;
    }
}
