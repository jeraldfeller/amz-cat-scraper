<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Scraper
{
    public $debug = TRUE;
    protected $db_pdo;

    public function getCategory(){
        $pdo = $this->getPdo();
        $sql = 'SELECT *
                  FROM `categories` WHERE `status` = 0 LIMIT 1
                  ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $content = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content[] = $row;
            $sqlUpdate = 'UPDATE `categories` SET `status` = 1 WHERE `id` = ' . $row['id'];
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute();
        }
        $pdo = null;
        return $content;
    }

    public function getProductLink(){
        $pdo = $this->getPdo();
        $sql = 'SELECT * FROM `product_link` WHERE `status` = 0 LIMIT 5';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $content = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content[] = $row;
            $sqlUpdate = 'UPDATE `product_link` SET `status` = 1 WHERE `id` = ' . $row['id'];
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute();
        }
        $pdo = null;

        return $content;
    }

    public function insertProductLink($name, $url, $asin, $price, $rank){
        $pdo = $this->getPdo();
        $sql = 'INSERT INTO `product_link` SET `category` = "'.$name.'", `url` = "'.$url.'", `asin` = "'.$asin.'", `price` = '.$price.', `rank` = '.$rank;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pdo = null;
    }


    public function updateProduct($id, $price){
        $pdo = $this->getPdo();
        $sql = 'UPDATE `product_link` SET `ts_seller` = '.$price . ' WHERE `id` = '.$id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pdo = null;
    }


    public function getProducts(){
        $pdo = $this->getPdo();
        $sql = 'SELECT * FROM `product_link` WHERE `status` = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $content = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content[] = $row;
        }
        $pdo = null;

        return $content;
    }

    public function sendOutPut()
    {
        $status = $this->checkStatus();
        if($status == true){
            $date = date('Y-m-d');
            $message = '<h2>Amazon Product Reports</h2><br>';
            $message .= 'Product list: ' . ROOT_DOMAIN . 'reports/amazon_products_' . $date . '.csv <br>';

            $email = new PHPMailer();
            $email->From = NO_REPLY_EMAIL;
            $email->FromName = NO_REPLY_EMAIL;
            $email->Subject = 'Ebay Product Reports';
            $email->Body = $message;
            $email->IsHTML(true);
            $email->AddAddress('jeraldfeller@gmail.com');
            $email->AddAddress(ADMIN_EMAIL);

            $return = $email->Send();
            if ($return) {
                $pdo = $this->getPdo();
                $sql = 'UPDATE `process_status` SET `is_sent` = 1 WHERE `id` = 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $pdo = null;
            }
            return $return;
        }

        return false;

    }

    public function checkStatus(){
        $pdo = $this->getPdo();
        $sql = 'SELECT * FROM `process_status` WHERE id = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content = $row;
        }


        // check if all products has been processed.
        $sql = 'SELECT count(id) as totalCount FROM `product_link` WHERE `status` = 0';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $return = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo = null;
        if($return['totalCount'] == 0 && $content['email_sent'] == 0){
            return true;
        }else{
            return false;
        }
    }



    public function reset(){
        $pdo = $this->getPdo();
        $sql = 'DELETE FROM `product_link`';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $sql = 'UPDATE `categories` SET `status` = 0';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $sql = 'UPDATE `proces_status` SET `status` = 0, `email_sent` = 0';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $pdo = null;
    }


    public function curlTo($url)
    {

        $port = '47647';
        $proxy = array(
            '45.59.21.152',
            '45.59.25.15',
            '206.223.253.17',
            '108.62.193.79',
            '23.19.36.161',
            '23.19.37.119',
        );

        $ip = $proxy[mt_rand(0, count($proxy) - 1)];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => $ip,
            CURLOPT_PROXYPORT => '47647',
            CURLOPT_PROXYUSERPWD => 'ebymarket:dfab7c358',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Postman-Token: d0401788-028f-4d14-903e-13f644905ee8",

            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return array('html' => $err);
        } else {
            return array('html' => $response, 'ip' => $ip);
        }
    }

    public function getPdo()
    {
        if (!$this->db_pdo) {
            if ($this->debug) {
                $this->db_pdo = new PDO(DB_DSN, DB_USER, DB_PWD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
            } else {
                $this->db_pdo = new PDO(DB_DSN, DB_USER, DB_PWD);
            }
        }
        return $this->db_pdo;
    }
}