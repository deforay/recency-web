<?php
namespace Application\Service;

use Zend\Session\Container;
use Exception;
use Zend\Db\Sql\Sql;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime as Mime;

class CommonService {

     public $sm = null;

     public function __construct($sm = null) {
          $this->sm = $sm;
     }

     public function getServiceManager() {
          return $this->sm;
     }

     public static function generateRandomString($length = 8, $seeds = 'alphanum') {
          // Possible seeds
          $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
          $seedings['numeric'] = '0123456789';
          $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
          $seedings['hexidec'] = '0123456789abcdef';

          // Choose seed
          if (isset($seedings[$seeds])) {
               $seeds = $seedings[$seeds];
          }

          // Seed generator
          list($usec, $sec) = explode(' ', microtime());
          $seed = (float) $sec + ((float) $usec * 100000);
          mt_srand($seed);

          // Generate
          $str = '';
          $seeds_count = strlen($seeds);

          for ($i = 0; $length > $i; $i++) {
               $str .= $seeds{mt_rand(0, $seeds_count - 1)};
          }

          return $str;
     }

     public function checkMultipleFieldValidations($params) {
          $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
          $jsonData = $params['json_data'];
          $tableName = $jsonData['tableName'];
          $sql = new Sql($adapter);
          $select = $sql->select()->from($tableName);
          foreach ($jsonData['columns'] as $val) {
               if ($val['column_value'] != "") {
                    $select->where($val['column_name'] . "=" . "'" . $val['column_value'] . "'");
               }
          }

          //edit
          if (isset($jsonData['tablePrimaryKeyValue']) && $jsonData['tablePrimaryKeyValue'] != null && $jsonData['tablePrimaryKeyValue'] != "null") {
               $select->where($jsonData['tablePrimaryKeyId'] . "!=" . $jsonData['tablePrimaryKeyValue']);
          }
          //error_log($sql);
          $statement = $sql->prepareStatementForSqlObject($select);
          $result = $statement->execute();
          $data = count($result);
          return $data;
     }


     public function checkFieldValidations($params) {
          $adapter = $this->sm->get('Zend\Db\Adapter\Adapter');
          $tableName = $params['tableName'];
          $fieldName = $params['fieldName'];
          $value = trim($params['value']);
          $fnct = $params['fnct'];
          try {
               $sql = new Sql($adapter);
               if ($fnct == '' || $fnct == 'null') {
                    $select = $sql->select()->from($tableName)->where(array($fieldName => $value));
                    //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."'");
                    $statement = $sql->prepareStatementForSqlObject($select);
                    $result = $statement->execute();
                    $data = count($result);
               } else {
                    $table = explode("##", $fnct);
                    if ($fieldName == 'password') {
                         //Password encrypted
                         $config = new \Zend\Config\Reader\Ini();
                         $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
                         $password = sha1($value . $configResult["password"]["salt"]);
                         //$password = $value;
                         $select = $sql->select()->from($tableName)->where(array($fieldName=>$password,$table[0]=>$table[1]));
                         $statement = $sql->prepareStatementForSqlObject($select);
                         $result = $statement->execute();
                         $data = count($result);
                    }else{
                         // first trying $table[1] without quotes. If this does not work, then in catch we try with single quotes
                         //$statement=$adapter->query('SELECT * FROM '.$tableName.' WHERE '.$fieldName." = '".$value."' and ".$table[0]."!=".$table[1] );
                         $select = $sql->select()->from($tableName)->where(array("$fieldName='$value'", $table[0] . "!=" . "'$table[1]'"));
                         $statement = $sql->prepareStatementForSqlObject($select);
                         $result = $statement->execute();
                         $data = count($result);
                    }
               }
               return $data;
          } catch (Exception $exc) {
               error_log($exc->getMessage());
               error_log($exc->getTraceAsString());
          }
     }

     public function dbDateFormat($date) {
          if (!isset($date) || $date == null || $date == "" || $date == "0000-00-00") {
               return "0000-00-00";
          } else {
               $dateArray = explode('-', $date);
               if(sizeof($dateArray) == 0 ){
                    return;
               }
               $newDate = $dateArray[2] . "-";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = 1;
               $mon += array_search(ucfirst($dateArray[1]), $monthsArray);

               if (strlen($mon) == 1) {
                    $mon = "0" . $mon;
               }
               return $newDate .= $mon . "-" . $dateArray[0];
          }
     }

     public function humanDateFormat($date) {
          if ($date == null || $date == "" || $date == "0000-00-00" || $date == "0000-00-00 00:00:00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate = $dateArray[2] . "-";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = $monthsArray[$dateArray[1] - 1];

               return $newDate .= $mon . "-" . $dateArray[0];
          }
     }

     public function viewDateFormat($date) {
          if ($date == null || $date == "" || $date == "0000-00-00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate = $dateArray[1] . "/". $dateArray[2]. "/".$dateArray[0];
               return $newDate;
          }
     }

     public function insertTempMail($to, $subject, $message, $fromMail, $fromName,$cc,$bcc) {
          $tempmailDb = $this->sm->get('TempMailTable');
          return $tempmailDb->insertTempMailDetails($to, $subject, $message, $fromMail, $fromName,$cc,$bcc);
     }


     public function sendTempMail() {
          try {
               $tempDb = $this->sm->get('TempMailTable');
               $globalDb = $this->sm->get('GlobalConfigTable');
               $config = new \Zend\Config\Reader\Ini();
               $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
               $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
               $sql = new Sql($dbAdapter);

               // Setup SMTP transport using LOGIN authentication
               $transport = new SmtpTransport();
               $options = new SmtpOptions(array(
                    'host' => $configResult["email"]["host"],
                    'port' => $configResult["email"]["config"]["port"],
                    'connection_class' => $configResult["email"]["config"]["auth"],
                    'connection_config' => array(
                         'username' => $configResult["email"]["config"]["username"],
                         'password' => $configResult["email"]["config"]["password"],
                         'ssl' => $configResult["email"]["config"]["ssl"],
                    ),
               ));
               $transport->setOptions($options);
               $limit = '10';
               $mailQuery = $sql->select()->from(array('tm' => 'temp_mail'))
               ->where("status='pending'")
               ->limit($limit);
               $mailQueryStr = $sql->getSqlStringForSqlObject($mailQuery);
               $mailResult = $dbAdapter->query($mailQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
               if (count($mailResult) > 0) {
                    foreach ($mailResult as $result) {
                         $alertMail = new Mail\Message();
                         $id = $result['temp_id'];
                         $tempDb->updateTempMailStatus($id);

                         $fromEmail = $result['from_mail'];
                         if(trim($result['attachment'])!=''){
                            $options = new SmtpOptions(array(
                            'host' => $configResult["email"]["host"],
                            'port' => $configResult["email"]["config"]["port"],
                            'connection_class' => $configResult["email"]["config"]["auth"],
                            'connection_config' => array(
                                'username' => $globalDb->getGlobalValue('email_id'),
                                'password' => $globalDb->getGlobalValue('email_password'),
                                'ssl' => $configResult["email"]["config"]["ssl"],
                            ),
                        ));
                        $transport->setOptions($options);
                        }
                         $fromFullName = $result['from_full_name'];
                         $subject = $result['subject'];

                         $html = new MimePart($result['message']);
                         $html->type = "text/html";

                         $body = new MimeMessage();
                         if(trim($result['attachment'])!=''){
                            $extension = strtolower(pathinfo($result['attachment'], PATHINFO_EXTENSION));
                            $attachment = new MimePart(fopen($result['attachment'],'r'));
                            if($extension=='pdf'){
                                $attachment->type = 'application/pdf';
                                $attachment->filename = 'vl-result.pdf';
                            }
                            $attachment->encoding    = Mime::ENCODING_BASE64;
                            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                            
                            $body->setParts(array($html,$attachment));
                        }else{
                            $body->setParts(array($html));
                        }

                         $alertMail->setBody($body);
                         $alertMail->addFrom($fromEmail, $fromFullName);
                         $alertMail->addReplyTo($fromEmail, $fromFullName);

                         $toArray = explode(",", $result['to_email']);
                         foreach ($toArray as $toId) {
                              if ($toId != '') {
                                   $alertMail->addTo($toId);
                              }
                         }
                         if (isset($result['cc']) && trim($result['cc']) != "") {
                              $ccArray = explode(",", $result['cc']);
                              foreach ($ccArray as $ccId) {
                                   if ($ccId != '') {
                                        $alertMail->addCc($ccId);
                                   }
                              }
                         }

                         if (isset($result['bcc']) && trim($result['bcc']) != "") {
                              $bccArray = explode(",", $result['bcc']);
                              foreach ($bccArray as $bccId) {
                                   if ($bccId != '') {
                                        $alertMail->addBcc($bccId);
                                   }
                              }
                         }

                         $alertMail->setSubject($subject);
                         $transport->send($alertMail);
                         $tempDb->deleteTempMail($id);
                    }
               }
          } catch (Exception $e) {
               error_log($e->getMessage());
               error_log($e->getTraceAsString());
               error_log('whoops! Something went wrong in cron/SendMailAlerts.php');
          }
     }

     function removeDirectory($dirname) {
          // Sanity check
          if (!file_exists($dirname)) {
               return false;
          }

          // Simple delete for a file
          if (is_file($dirname) || is_link($dirname)) {
               return unlink($dirname);
          }

          // Loop through the folder
          $dir = dir($dirname);
          while (false !== $entry = $dir->read()) {
               // Skip pointers
               if ($entry == '.' || $entry == '..') {
                    continue;
               }

               // Recurse
               $this->removeDirectory($dirname . DIRECTORY_SEPARATOR . $entry);
          }

          // Clean up
          $dir->close();
          return rmdir($dirname);
     }

     public function removespecials($url){
          $url=str_replace(" ","-",$url);

          $url = preg_replace('/[^a-zA-Z0-9\-]/', '', $url);
          $url = preg_replace('/^[\-]+/', '', $url);
          $url = preg_replace('/[\-]+$/', '', $url);
          $url = preg_replace('/[\-]{2,}/', '', $url);

          return strtolower($url);
     }

     public static function getDateTime($timezone = 'Asia/Calcutta') {
          $date = new \DateTime(date('Y-m-d H:i:s'), new \DateTimeZone($timezone));
          return $date->format('Y-m-d H:i:s');
     }

     public static function getDate($timezone = 'Asia/Calcutta') {
          $date = new \DateTime(date('Y-m-d'), new \DateTimeZone($timezone));
          return $date->format('Y-m-d');
     }

     public function humanMonthlyDateFormat($date) {
          if ($date == null || $date == "" || $date == "0000-00-00" || $date == "0000-00-00 00:00:00") {
               return "";
          } else {
               $dateArray = explode('-', $date);
               $newDate =  "";

               $monthsArray = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
               $mon = $monthsArray[$dateArray[1]*1];

               return $newDate .= $mon . " " . $dateArray[0];
          }
     }
     public function dbDateTimeFormat($dateTime){
          return date('d-M-Y H:i A', strtotime($dateTime));

     }

     public function getAllProvienceListApi()
     {
         $provienceDb = $this->sm->get('ProvinceTable');
         return $provienceDb->fetchAllProvienceListApi();
     }

     public function getAllDistrictListApi($params)
     {
         $districtDb = $this->sm->get('DistrictTable');
         return $districtDb->fetchAllDistrictListApi($params);
     }

     public function getAllCityListApi($params)
     {
         $cityDb = $this->sm->get('CityTable');
         return $cityDb->fetchAllCityListApi($params);
     }

     public function getProvinceDetails($params)
     {
         $provienceDb = $this->sm->get('ProvinceTable');
         return $provienceDb->fetchProvinceDetails($params);
     }
     public function getDistrictDetails($params)
     {
         $provienceDb = $this->sm->get('DistrictTable');
         return $provienceDb->fetchDistrictDetails($params);
     }
     public function getCityDetails($params)
     {
         $provienceDb = $this->sm->get('CityTable');
         return $provienceDb->fetchCityDetails($params);
     }

     public function getCaptcha($config = array()) {
        if (!function_exists('gd_info')) {
            throw new Exception('Required GD library is missing');
        }

        // Default values
        $captcha_config = array(
            'code' => '',
            'min_length' => 4,
            'max_length' => 5,
            'png_backgrounds' => array(UPLOAD_PATH . '/../assets/images/captchabg/default.png',UPLOAD_PATH . '/../assets/images/captchabg/ravenna.png'),
            'fonts' => array(UPLOAD_PATH . '/../assets/fonts/font/Idolwild/idolwild.ttf'),
            // 'characters' => 'abcdefghijkmpsxyz23456789abcdefghijkmpsxyz23456789abcdefghijkmpsxyz23456789',
            'characters' => '1234567890',
            'min_font_size' => 22,
            'max_font_size' => 26,
            'color' => '#000',
            'angle_min' => 0,
            'angle_max' => 10,
            'shadow' => true,
            'shadow_color' => '#bbb',
            'shadow_offset_x' => -2,
            'shadow_offset_y' => 1
        );

        // Overwrite defaults with custom config values
        if (is_array($config)) {
            foreach ($config as $key => $value)
                $captcha_config[$key] = $value;
        }

        // Restrict certain values
        if ($captcha_config['min_length'] < 1)
            $captcha_config['min_length'] = 1;
        if ($captcha_config['angle_min'] < 0)
            $captcha_config['angle_min'] = 0;
        if ($captcha_config['angle_max'] > 10)
            $captcha_config['angle_max'] = 10;
        if ($captcha_config['angle_max'] < $captcha_config['angle_min'])
            $captcha_config['angle_max'] = $captcha_config['angle_min'];
        if ($captcha_config['min_font_size'] < 10)
            $captcha_config['min_font_size'] = 10;
        if ($captcha_config['max_font_size'] < $captcha_config['min_font_size'])
            $captcha_config['max_font_size'] = $captcha_config['min_font_size'];

        // Use milliseconds instead of seconds
        //srand(microtime() * 100);

        // Generate CAPTCHA code if not set by user
        if (empty($captcha_config['code'])) {
            $captcha_config['code'] = '';
            $length = rand($captcha_config['min_length'], $captcha_config['max_length']);
            while (strlen($captcha_config['code']) < $length) {
                $captcha_config['code'] .= substr($captcha_config['characters'], rand() % (strlen($captcha_config['characters'])), 1);
            }
        }

        // Generate image src
        $image_src = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT'])) . '?DACAPTCHA&amp;t=' . urlencode(microtime());
        $image_src = '/' . ltrim(preg_replace('/\\\\/', '/', $image_src), '/');


        $captchaSession = new Container('captcha');
        $captchaSession->code = trim($captcha_config['code']);


        if (!function_exists('hex2rgb')) {

            function hex2rgb($hex_str, $return_string = false, $separator = ',') {
                $hex_str = preg_replace("/[^0-9A-Fa-f]/", '', $hex_str); // Gets a proper hex string
                $rgb_array = array();
                if (strlen($hex_str) == 6) {
                    $color_val = hexdec($hex_str);
                    $rgb_array['r'] = 0xFF & ($color_val >> 0x10);
                    $rgb_array['g'] = 0xFF & ($color_val >> 0x8);
                    $rgb_array['b'] = 0xFF & $color_val;
                } elseif (strlen($hex_str) == 3) {
                    $rgb_array['r'] = hexdec(str_repeat(substr($hex_str, 0, 1), 2));
                    $rgb_array['g'] = hexdec(str_repeat(substr($hex_str, 1, 1), 2));
                    $rgb_array['b'] = hexdec(str_repeat(substr($hex_str, 2, 1), 2));
                } else {
                    return false;
                }
                return $return_string ? implode($separator, $rgb_array) : $rgb_array;
            }

        }


        //srand(microtime() * 100);

        // Pick random background, get info, and start captcha
        $background = $captcha_config['png_backgrounds'][rand(0, count($captcha_config['png_backgrounds']) - 1)];
        list($bg_width, $bg_height, $bg_type, $bg_attr) = getimagesize($background);
        // Create captcha object
        $captcha = imagecreatefrompng($background);
        imagealphablending($captcha, true);
        imagesavealpha($captcha, true);

        $color = hex2rgb($captcha_config['color']);
        $color = imagecolorallocate($captcha, $color['r'], $color['g'], $color['b']);

        // Determine text angle
        $angle = rand($captcha_config['angle_min'], $captcha_config['angle_max']) * (rand(0, 1) == 1 ? -1 : 1);

        // Select font randomly
        $font = $captcha_config['fonts'][rand(0, count($captcha_config['fonts']) - 1)];

        // Verify font file exists
        if (!file_exists($font))
            throw new Exception('Font file not found: ' . $font);

        //Set the font size.
        $font_size = rand($captcha_config['min_font_size'], $captcha_config['max_font_size']);
        $text_box_size = imagettfbbox($font_size, $angle, $font, $captcha_config['code']);

        // Determine text position
        $box_width = abs($text_box_size[6] - $text_box_size[2]);
        $box_height = abs($text_box_size[5] - $text_box_size[1]);
        $text_pos_x_min = 0;
        $text_pos_x_max = ($bg_width) - ($box_width);
        $text_pos_x = rand($text_pos_x_min, $text_pos_x_max);
        $text_pos_y_min = $box_height;
        $text_pos_y_max = ($bg_height) - ($box_height / 2);
        $text_pos_y = rand($text_pos_y_min, $text_pos_y_max);

        // Draw shadow
        if ($captcha_config['shadow']) {
            $shadow_color = hex2rgb($captcha_config['shadow_color']);
            $shadow_color = imagecolorallocate($captcha, $shadow_color['r'], $shadow_color['g'], $shadow_color['b']);
            imagettftext($captcha, $font_size, $angle, $text_pos_x + $captcha_config['shadow_offset_x'], $text_pos_y + $captcha_config['shadow_offset_y'], $shadow_color, $font, $captcha_config['code']);
        }

        // Draw text
        imagettftext($captcha, $font_size, $angle, $text_pos_x, $text_pos_y, $color, $font, $captcha_config['code']);

        // Output image
        header("Content-type: image/png");
        imagepng($captcha);
    }
}

?>
