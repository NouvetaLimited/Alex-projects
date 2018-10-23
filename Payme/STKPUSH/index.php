<?php
/**
 * Created by PhpStorm.
 * User: AlexBoey
 * Date: 4/24/2017
 * Time: 5:55 PM
 */

include 'includes/DB.php';
include 'helpers/Response.php';
include 'helpers/ConfirmationCode.php';
include "helpers/AfricasTalkingGateway.php";
include 'helpers/fpdf/fpdf.php';
include 'PHPMailer/PHPMailerAutoload.php';

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");



if(empty($_REQUEST['function'])){
    $function = $_REQUEST['TransactionType'];
}else{
    $function = $_REQUEST['function'];
}


function generateToke($consumer_key,$consumer_secrete) {

    //$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $keySecrete = $consumer_key.':'.$consumer_secrete;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($keySecrete);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials)); //setting a custom header
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $curl_response = curl_exec($curl);

    $result = json_decode($curl_response);

    $token = $result->access_token;
    return $token;

}
function getTimestamp() {
    $string =(date("Y-m-d H:i:s"));
    $string = str_replace('-', '', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

    return preg_replace('/-+/', '-', $string);
}
function getPassword($PayBillNumber,$pass_key,$time){

    return base64_encode($PayBillNumber.$pass_key.$time);
}

if($function==="CustomerPayBillOnline"){

  /*  //Get consumer key and consumer secrete
    if(empty($_POST['PayBillNumber'])){
        $PayBillNumber = $_GET['PayBillNumber'];
        $Amount = $_GET['Amount'];
        $PhoneNumber =formatPhoneNumber( $_GET['PhoneNumber']);
        $AccountReference = $_GET['AccountReference'];
        $TransactionDesc ='customer';
        $TransactionDes = $_GET['TransactionDesc'];
    }else{
        $PayBillNumber = $_POST['PayBillNumber'];
        $Amount = $_POST['Amount'];
        $PhoneNumber =formatPhoneNumber( $_POST['PhoneNumber']);
        $AccountReference = $_POST['AccountReference'];
        $TransactionDesc ='customer';
        $TransactionDes = $_POST['TransactionDesc'];
    }*/

    $PayBillNumber = $_REQUEST['PayBillNumber'];
    $Amount = $_REQUEST['Amount'];
    $PhoneNumber =formatPhoneNumber( $_REQUEST['PhoneNumber']);
    $AccountReference = $_REQUEST['AccountReference'];
    $TransactionDesc ='customer';
    $TransactionDes = $_REQUEST['TransactionDesc'];
    /* if($TransactionDes=="PAYMENT")
         exit();*/

    $sql ="SELECT * FROM `configuration` WHERE `short_code` ='$PayBillNumber'";
    $result = DB::instance()->executeSQL($sql);


    if ($result->num_rows>0){
        $response = new Response();
        $response->data = $result->fetch_assoc();
        $consumer_key =$response->data['consumer_key'];
        $consumer_secret =$response->data['consumer_secret'];
        $pass_key = $response->data['pass_key'];


        $Timestamp = getTimestamp();
        $Password = getPassword($PayBillNumber,$pass_key,$Timestamp);
        $TransactionType ='CustomerPayBillOnline';
        if(empty($_POST['mobileNotification'])){
            $mobileNotification='NULL';

        }else{
            $mobileNotification = $_POST['mobileNotification'];
        }
        // $CallBackURL = 'https://gyqytzjjvf.localtunnel.me/nouveta/STKPUSH/callBack.php';
        $CallBackURL = 'https://payme.nouveta.co.ke/api/callBack.php';
        $token = generateToke($consumer_key,$consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        set_time_limit(0);
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest",
            //CURLOPT_URL => "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n      \"BusinessShortCode\": \"$PayBillNumber\",\r\n      \"Password\": \"$Password\",\r\n      \"Timestamp\": \"$Timestamp\",\r\n      \"TransactionType\": \"$TransactionType\",\r\n      \"Amount\": \"$Amount\",\r\n      \"PartyA\": \"$PhoneNumber\",\r\n      \"PartyB\": \"$PayBillNumber\",\r\n      \"PhoneNumber\": \"$PhoneNumber\",\r\n      \"CallBackURL\": \"$CallBackURL\",\r\n      \"AccountReference\": \"$AccountReference\",\r\n      \"TransactionDesc\": \"$TransactionDesc\"\r\n    }",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer $token",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 52c1d241-e51e-de4e-f4af-4edc55383daf"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $data = json_decode($response);
        $MerchantRequestID=$data->MerchantRequestID;
        saveRequest($PayBillNumber,$MerchantRequestID,$AccountReference,$TransactionDes,$mobileNotification,$Amount,$PhoneNumber);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {

            echo $response;
        }

    }else{

        $response = new Response();
        $response->message="Paybill configurations not found";
        $response->success=false;
        echo json_encode($response);

    }



}
if($function==="configure"){
    $app_name = $_POST['app_name'];
    $short_code=$_POST['short_code'];
    $pass_key = $_POST['pass_key'];
    $consumer_key = $_POST['consumer_key'];
    $consumer_secret=$_POST['consumer_secret'];
    $business_name=$_POST['business_name'];
    $callback = $_POST['callback'];
    $sender_id = $_POST['sender_id'];
    $message = $_POST['message'];
    $sms = $_POST['sms'];
    $tariff = $_POST['tariff'];
    $tariff_value = $_POST['tariff_value'];
    $mastercard_merchant_id = $_POST['mastercard_merchant_id'];
    $checked_services = $_POST['checked_services'];
    $secure_key = $_POST['secure_key'];

    //check if exits
    $sql ="SELECT * FROM `configuration` WHERE `short_code`='$short_code'";
    $result = DB::instance()->executeSQL($sql);


    if($result->num_rows<1){

        $sql ="INSERT INTO `configuration`(`app_name`, `short_code`, `pass_key`, `consumer_key`, `consumer_secret`, `business_name`,`callback`,`message`,`sender_id`,`sms`,`tariff`,`tariff_value`,`mastercard_merchant_id`,`checked_services`,`secure_key`)
                               VALUES ('$app_name','$short_code','$pass_key','$consumer_key','$consumer_secret','$business_name','$callback','$message','$sender_id','$sms','$tariff','$tariff_value','$mastercard_merchant_id','$checked_services','$secure_key')";


        $result = DB::instance()->executeSQL($sql);

        if($result){
            $response = new Response();
            $response->message="Configured success";
            $response->success= true;
            echo json_encode($response);

        }else{
            $response = new Response();
            $response->message="Configured failed";
            $response->success= false;
            echo json_encode($response);
        }

    }else{
        $response = new Response();
        $response->message="Exits";
        $response->success= false;
        echo json_encode($response);

    }


}
if($function==="editConfigure"){
    $id =$_REQUEST['id'];
    $app_name = $_REQUEST['app_name'];
    $short_code=$_REQUEST['short_code'];
    $pass_key = $_REQUEST['pass_key'];
    $consumer_key = $_REQUEST['consumer_key'];
    $consumer_secret=$_REQUEST['consumer_secret'];
    $business_name=$_REQUEST['business_name'];
    $callback = $_REQUEST['callback'];
    $sender_id = $_REQUEST['sender_id'];
    $message = $_REQUEST['message'];
    $sms = $_REQUEST['sms'];
    $tariff = $_REQUEST['tariff'];
    $tariff_value = $_REQUEST['tariff_value'];
    $company = $_REQUEST['company'];
    $mastercard_merchant_id = $_POST['mastercard_merchant_id'];
    $checked_services = $_POST['checked_services'];
    $secure_key = $_POST['secure_key'];

    //check if exits
    $sql ="UPDATE `configuration` SET `app_name`='$app_name',`short_code`='$short_code',`pass_key`='$pass_key',`consumer_key`='$consumer_key',`consumer_secret`='$consumer_secret',`business_name`='$business_name',`callback`='$callback',`message`='$message',`sender_id`='$sender_id',`sms`='$sms',`tariff`='$tariff',`tariff_value`='$tariff_value',`company`='$company'
     ,`mastercard_merchant_id`='$mastercard_merchant_id',`checked_services`='$checked_services',`secure_key`='$secure_key' WHERE `id`='$id'";
    $result = DB::instance()->executeSQL($sql);


    if($result){
        $response = new Response();
        $response->message="Saved success";
        $response->success= true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->message="Configured failed";
        $response->success= false;
        echo json_encode($response);
    }


}
function saveRequest($PayBillNumber,$MerchantRequestID,$AccountReference,$TransactionDesc,$mobileNotification,$amount,$phone){

    $sql ="INSERT INTO `push_request`(`paybill_number`, `merchant_request_id`,`account_reference`,`TransactionDesc`,`mobileNotification`,`amount`,`phone`) 
                            VALUES ('$PayBillNumber','$MerchantRequestID','$AccountReference','$TransactionDesc','$mobileNotification','$amount','$phone')";

    DB::instance()->executeSQL($sql);

}
if($function=="registerUrl"){
    $ShortCode = $_REQUEST['ShortCode'];
    $ConfirmationURL = $_REQUEST['ConfirmationURL'];
    $ValidationURL = $_REQUEST['ValidationURL'];

    $sql ="SELECT * FROM `configuration` WHERE `short_code` ='$ShortCode'";
    $result = DB::instance()->executeSQL($sql);


    if ($result->num_rows>0) {
        $response = new Response();
        $response->data = $result->fetch_assoc();
        $consumer_key = $response->data['consumer_key'];
        $consumer_secret = $response->data['consumer_secret'];
        $pass_key = $response->data['pass_key'];

        $ACCESS_TOKEN = generateToke($consumer_key, $consumer_secret);



        // $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';
        $url = 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$ACCESS_TOKEN)); //setting custom header


        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'ShortCode' => $ShortCode,
            'ResponseType' => 'json',
            'ConfirmationURL' => $ConfirmationURL,
            'ValidationURL' => $ValidationURL
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);
        print_r($curl_response);

        echo $curl_response;

    }else{

        $response = new Response();
        $response->message="Paybill configurations not found";
        $response->success=false;
        echo json_encode($response);


    }

}
if($function==="B2BPaymentRequest"){

    $PayBillNumber = $_REQUEST['PayBillNumber'];
    $PayBillNumber2 = $_REQUEST['PayBillNumber2'];
    $Amount = $_REQUEST['Amount'];

    $sql ="SELECT * FROM `configuration` WHERE `short_code` ='$PayBillNumber'";
    $result = DB::instance()->executeSQL($sql);


    if ($result->num_rows>0) {
        $response = new Response();
        $response->data = $result->fetch_assoc();
        $consumer_key = $response->data['consumer_key'];
        $consumer_secret = $response->data['consumer_secret'];
        $pass_key = $response->data['pass_key'];
        $security_credential = $response->data['security_credential'];
        $initiator = $response->data['initiator'];

        //$url = 'https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest';
        $url = 'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest';

        $ACCESS_TOKEN = generateToke($consumer_key, $consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $ACCESS_TOKEN)); //setting custom header

        $SecurityCredential = $security_credential;
        //#Megatrek55
        $curl_post_data = array(
            'Initiator' => $initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => 'BusinessPayBill',
            'SenderIdentifierType' => '4',
            'RecieverIdentifierType' => '4',
            'Amount' => $Amount,
            'PartyA' => $PayBillNumber,
            'PartyB' => $PayBillNumber2,
            'AccountReference' => '1234',
            'Remarks' => 'test',
            'QueueTimeOutURL' => 'https://payme.nouveta.co.ke/api/callBack.php',
            'ResultURL' => 'https://payme.nouveta.co.ke/api/callBack.php'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);
        print_r($curl_response);

        echo $curl_response;
    }else{

        $response = new Response();
        $response->message="Paybill configurations not found";
        $response->success=false;
        echo json_encode($response);


    }

}
if($function==="B2CPaymentRequest"){

    $PayBillNumber = $_REQUEST['PayBillNumber'];
    $MSDN = $_REQUEST['phoneNumber'];
    $Amount = $_REQUEST['Amount'];

    $sql ="SELECT * FROM `configuration` WHERE `short_code` ='$PayBillNumber'";
    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows>0) {
        $response = new Response();
        $response->data = $result->fetch_assoc();
        $consumer_key = $response->data['consumer_key'];
        $consumer_secret = $response->data['consumer_secret'];
        $pass_key = $response->data['pass_key'];
        $security_credential = $response->data['security_credential'];
        $initiator = $response->data['initiator'];

        //$url = 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';
        $url = 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $ACCESS_TOKEN = generateToke($consumer_key, $consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $ACCESS_TOKEN)); //setting custom header

        $SecurityCredential = $security_credential;
        //#Megatrek55
        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'InitiatorName' => $initiator,
            'SecurityCredential' => $SecurityCredential,
            'CommandID' => 'BusinessPayment',
            'Amount' => $Amount,
            'PartyA' => $PayBillNumber,
            'PartyB' => $MSDN,
            'Remarks' => 'Test',
            'QueueTimeOutURL' => 'https://nouveta.co.ke/',
            'ResultURL' => 'https://payme.nouveta.co.ke/api/callBack.php',
            'Occasion' => '2018'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);
        print_r($curl_response);
        echo $curl_response;
    }else{

        $response = new Response();
        $response->message="Paybill configurations not found";
        $response->success=false;
        echo json_encode($response);


    }

}
if($function=="AccountBalanceRequest"){


    $PayBillNumber = $_REQUEST['PayBillNumber'];

    $sql ="SELECT * FROM `configuration` WHERE `short_code` ='$PayBillNumber'";
    $result = DB::instance()->executeSQL($sql);


    if ($result->num_rows>0) {
        $response = new Response();
        $response->data = $result->fetch_assoc();
        $consumer_key = $response->data['consumer_key'];
        $consumer_secret = $response->data['consumer_secret'];
        $pass_key = $response->data['pass_key'];


        //$url = 'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query';
        $url = 'https://api.safaricom.co.ke/mpesa/accountbalance/v1/query';

        $ACCESS_TOKEN =generateToke($consumer_key,$consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer ' .$ACCESS_TOKEN)); //setting custom header

        $SecurityCredential =getPassword($PayBillNumber,$pass_key,getTimestamp());
        $curl_post_data = array(
            //Fill in the request parameters with valid values
            'Initiator' => 'Hezron Muriuki',
            'SecurityCredential' =>$SecurityCredential,
            'CommandID' => 'AccountBalance',
            'PartyA' => $PayBillNumber,
            'IdentifierType' => '4',
            'Remarks' => 'Test',
            'QueueTimeOutURL' => 'https://nouveta.co.ke/',
            'ResultURL' => 'https://payme.nouveta.co.ke/api/callBack.php'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);
        //  print_r($curl_response);

        echo $curl_response;


    }else{

        $response = new Response();
        $response->message="Paybill configurations not found";
        $response->success=false;
        echo json_encode($response);

    }



}
if($function=="mail"){
    $mailto = 'wanjalaalex25@gmail.com';
    $mailSub = 'UPGRADE REQUEST';
    $mailMsg = "hello";
    $mail = new PHPMailer();
    $mail ->IsSmtp();
    $mail ->SMTPDebug = 0;
    $mail ->SMTPAuth = true;
    $mail ->SMTPSecure = 'ssl';
    $mail ->Host = "smtp.gmail.com";
    $mail ->Port = 465; // or 587
    $mail ->IsHTML(true);
    $mail ->Username = "zukucustomer@gmail.com";
    $mail ->Password = "#megatrek55";
    $mail ->SetFrom("zukucustomer@gmail.com");
    $mail ->Subject = $mailSub;
    $mail ->Body = $mailMsg;
    $mail ->AddAddress($mailto);

    if(!$mail->Send())
    {
        echo $mail->ErrorInfo;
    }
    else
    {
        // return "Thank you, we have received your upgrade request, our team will respond shortly with details on how to upgrade";
        echo "Webale, Tufunye okusaba kwo, ojja kufuna okuddibwamu kukukyusa empeereza yo mukaseera katono";
    }
}
if($function=="getBusinesses"){

    $sql ="SELECT * FROM `configuration`";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        while ($row =$result->fetch_assoc()){
            $new_array[] = $row;
        }

        $response = new Response();
        $response->data = $new_array;
        $response->success = true;
        echo json_encode($response);

    }


}
if($function=="getBusinessesPayBill"){

    $company = $_REQUEST['company'];

    $response = new Response();
    $response->data = getBusinessesPayBill($company);
    $response->success = true;
    echo json_encode($response);


}
function getBusinessesPayBill($company){
    $sql ="SELECT * FROM `users` WHERE `company`='$company'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $type = $result->fetch_assoc()['type'];
        if($type==="superAdmin"){
            $sql ="SELECT * FROM `configuration`";
        }else{
            $sql ="SELECT * FROM `configuration` WHERE `company`='$company'";
        }
    }

    $result = DB::instance()->executeSQL($sql);

    if($result){
        while ($row =$result->fetch_assoc()){
            $new_array[] = $row;
        }
        return $new_array;


    }

}
if($function=="transactions") {

    //todO fetch per payBill
    $company = $_REQUEST['company'];
    $payment_mode = $_REQUEST['transactionType'];

    $sql ="SELECT * FROM `users` WHERE `company`='$company'";
    $result = DB::instance()->executeSQL($sql);

    if($result) {
        $type = $result->fetch_assoc()['type'];

        if (!empty($_REQUEST['payBill'])) {
            //fetch transactions for by payBills
            $payBill = $_REQUEST['payBill'];
            if (!empty($_REQUEST['dateTo']) && !empty($_REQUEST['dateFrom'])) {
                $dateTo = date('Y-m-d H:i:s', strtotime($_REQUEST['dateTo']));
                $dateFrom = date('Y-m-d H:i:s', strtotime($_REQUEST['dateFrom']));

                if (!empty($_REQUEST['keyword'])) {
                    $keyword = $_REQUEST['keyword'];

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND  `account_to` ='$payBill'  AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND  `account_to` ='$payBill' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo' ";

                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND  `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND  `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo' ";

                    }


                } else {

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE  `status`='success' AND `account_to` ='$payBill'  AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND  `account_to` ='$payBill' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo' ";


                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND  `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND  `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo' ";

                    }


                }

            } else {

                //current month
                $dateTo = date('Y-m-t 12:59:59', strtotime('this month'));
                $dateFrom = date('Y-m-01 00:00:00', strtotime('this month'));

                if (!empty($_REQUEST['keyword'])) {
                    $keyword = $_REQUEST['keyword'];

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `account_to` ='$payBill' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `account_to` ='$payBill' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";


                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                    }


                } else {

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `account_to` ='$payBill'  AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `account_to` ='$payBill' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";


                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `account_to` ='$payBill' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                    }


                }


            }

        } else {
            //fetch transactions for all paybills
            if (!empty($_REQUEST['dateTo']) && !empty($_REQUEST['dateFrom'])) {
                $dateTo = date('Y-m-d H:i:s', strtotime($_REQUEST['dateTo']));
                $dateFrom = date('Y-m-d H:i:s', strtotime($_REQUEST['dateFrom']));

                if (!empty($_REQUEST['keyword'])) {
                    $keyword = $_REQUEST['keyword'];

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE  `status`='success' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND   `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";


                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND  `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%'  OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                    }


                } else {

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND  `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                    }



                }

            } else {

                //current month
                $dateTo = date('Y-m-t 12:59:59', strtotime('this month'));
                $dateFrom = date('Y-m-01 00:00:00', strtotime('this month'));

                if (!empty($_REQUEST['keyword'])) {
                    $keyword = $_REQUEST['keyword'];

                    if($type==="superAdmin"){
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND  `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";


                    }else{
                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND  `account_to` LIKE '%$keyword%' OR `account_from`  LIKE '%$keyword%' OR `amount`  LIKE '%$keyword%' OR  `ref` LIKE '%$keyword%' OR `transaction_code` LIKE '%$keyword%' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";


                    }

                } else {

                    if($type==="superAdmin"){

                        $sql = "SELECT * FROM `transactions` WHERE `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

                    }else{

                        $sql = "SELECT * FROM `transactions` WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                        $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                      WHERE `status`='success' AND `company` ='$company' AND `payment_mode` ='$payment_mode' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";
                    }



                }


            }

        }
    }

    $res = DB::instance()->executeSQL($sqlTotal);
    $result = DB::instance()->executeSQL($sql);


    if ($result) {
        $new_array=null;
        while ($row = $result->fetch_assoc()) {
            $new_array[] = $row;
        }

        $response = new Response();
        $response->message = $res->fetch_array()[0].":".$result->num_rows;
        $response->data = $new_array;
        $response->success = true;
        echo json_encode($response);

    }
}
if($function =="login"){
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];

    login($email, $password);
}
if($function=="addCompany"){
    $company = $_REQUEST['company'];
    $asingedPaybill = $_REQUEST['asingedPaybill'];
    $callback = $_REQUEST['callback'];

    $sql ="INSERT INTO `companies`(`company`, `asingedPaybill`, `callback`) 
                           VALUES ('$company','$asingedPaybill','$callback')";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Added";
        $response->success = true;
        echo json_encode($response);
        exit();
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Not Added";
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}
function login ($email, $password) {

    $sql ="SELECT * FROM `users` WHERE `email` ='$email' /*AND `password` ='$password'*/";
    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if($user['status']=="Approved"){
                if($user['password']==$password){
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message = 'Login successfully';
                    $response->success = true;
                    $response->data = $user;
                    $response->data2 = getBusinessesPayBill($user['company']);
                    echo json_encode($response);

                }else{
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message = 'Login failed';
                    $response->success = false;
                    echo json_encode($response);
                }
            }else{
                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->message = 'Account registration was successful. Please wait as your account is being approved';
                $response->success = false;
                echo json_encode($response);
            }
        }
        else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Please check your credentials and try again. Create an account if you have none ';
            $response->success = false;
            echo json_encode($response);
        }

    }else {

        $sql = "SELECT * FROM `users` WHERE `company` ='$email' AND `password` ='$password'";
        $result = DB::instance()->executeSQL($sql);
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if($user['status']=="Approved"){
                if($user['password']==$password){
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message = 'Login successfully';
                    $response->success = true;
                    $response->data = $user;
                    $response->data2 = getBusinessesPayBill($user['company']);
                    echo json_encode($response);

                }else{
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message = 'Login failed';
                    $response->success = false;
                    echo json_encode($response);
                }
            }else{
                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->message = 'Account registration was successful. Please wait as your account is being approved';
                $response->success = false;
                echo json_encode($response);
            }
        }
        else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Please check your credentials and try again. Create an account if you have none ';
            $response->success = false;
            echo json_encode($response);
        }

    }



}
function apiCredentials ($userName, $password) {

    $sql ="SELECT * FROM `users` WHERE `email` ='$userName' AND `password` ='$password'";
    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if($user['status']=="Approved"){
            if($user['password']==$password){

                return true;
            }else{
                /*  $response = new Response();
                  $response->status = Response::STATUS_SUCCESS;
                  $response->message = 'Invalid Loging credentails';
                  $response->success = false;
                  echo json_encode($response);*/

                return false;
            }
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Account registration was successful. Please wait as your account is being approved';
            $response->success = false;
            echo json_encode($response);
        }
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Please check your credentials and try again';
        $response->success = false;
        echo json_encode($response);
    }

}
function formatPhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^\dxX]/', '', $phoneNumber);
    $phoneNumber = preg_replace('/^0/','254',$phoneNumber);

    $phoneNumber = $phone = preg_replace('/\D+/', '', $phoneNumber);

    return $phoneNumber;
}
function Redirect($url, $permanent = false)
{
    if (headers_sent() === false)
    {
        header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }

    exit();
}

//public api
if($function=="getTransactions"){
    $payBill = $_REQUEST['PaybillNumber'];


    if (!empty($_REQUEST['dateTo']) && !empty($_REQUEST['dateFrom'])) {
        $dateTo = date('Y-m-d H:i:s', strtotime($_REQUEST['dateTo']));
        $dateFrom = date('Y-m-d H:i:s', strtotime($_REQUEST['dateFrom']));
        $sql = "SELECT `id`, `account_from`, `amount`, `ref`, `transaction_code`, `payment_mode`, `status`, `date` ,`merchant_request_id` FROM `transactions` WHERE `account_to`='$payBill' AND `date` BETWEEN '$dateFrom' AND  '$dateTo'";

    }else{
        $sql = "SELECT `id`, `account_from`, `amount`, `ref`, `transaction_code`, `payment_mode`, `status`, `date` ,`merchant_request_id` FROM `transactions` WHERE `account_to`='$payBill'";

    }

    $result = DB::instance()->executeSQL($sql);
    if($result->num_rows > 0){
        $new_array=null;
        while ($row = $result->fetch_assoc()) {
            $new_array[] = $row;
        }

        $response = new Response();
        $response->message = "Transactions";
        $response->data = $new_array;
        $response->success = true;
        echo json_encode($response);

    }else{

        $response = new Response();
        $response->message = "No Transactions found for ".$payBill;
        $response->success = false;
        echo json_encode($response);

    }
}

if($function=="searchTransactions"){

    $merchant_request_id = $_REQUEST['MerchantRequestID'];

    $sql = "SELECT `id`, `account_from`, `amount`, `ref`, `transaction_code`, `payment_mode`, `status`, `date` ,`merchant_request_id` FROM `transactions` WHERE `merchant_request_id`='$merchant_request_id'";


    $result = DB::instance()->executeSQL($sql);
    if($result->num_rows > 0){
        $new_array=null;
        while ($row = $result->fetch_assoc()) {
            $new_array[] = $row;
        }

        $response = new Response();
        $response->message = "Transactions";
        $response->data = $new_array;
        $response->success = true;
        echo json_encode($response);

    }else{

        $response = new Response();
        $response->message = "No Transactions found for ".$AccountReference;
        $response->success = false;
        echo json_encode($response);

    }
}

// New Updates APi

function referenceCode(){
    $today = date("d");
    $rand = strtoupper(substr(uniqid(sha1(time())),0,4));

    return $unique = $today . $rand;
}
function sendSMS($phoneNumber,$message){
    $username   = "Nouveta";
    $apikey     = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";

    $gateway    = new AfricasTalkingGateway($username, $apikey);
    try
    {
        // Thats it, hit send and we'll take care of the rest.
        $results = $gateway->sendMessage($phoneNumber, $message,'NOUVETA');

        foreach($results as $result) {
            /* // status is either "Success" or "error message"
             echo " Number: " .$result->number;
             echo " Status: " .$result->status;
             echo " MessageId: " .$result->messageId;
             echo " Cost: "   .$result->cost."\n";*/
        }
    }
    catch ( AfricasTalkingGatewayException $e )
    {
        //  echo "Encountered an error while sending: ".$e->getMessage();
    }

}

if(!empty($_POST['function']))
    $function = $_POST['function'];

if(!empty($_GET['function']))
    $function = $_GET['function'];


if(!empty($_REQUEST['function']))
    $function = $_REQUEST['function'];

if($function=='cash'){

    $accountTo=$_POST['agentNo'];
    $accountFrom=$_POST['phoneNumber'];
    $amount=$_POST['amount'];
    $refCode=referenceCode();
    $method="Cash";
    $status="Pending";

    $sql="INSERT INTO `transactions`(`account_to`, `account_from`, `amount`,`ref`,`payment_mode`,`status`)
            values ('$accountTo','$accountFrom','$amount','$refCode','$method','$status')";

    $result = DB::instance()->executeSQL($sql);
    if ($result) {
        $message="Your request has been recieved your refCode is:$refCode for amount :$amount.Once payment is confirmed you will receive a confirmation code";

        sendSMS($accountFrom,$message);

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "information updated";
        $response->data = $refCode;
        $response->success = true;
        echo json_encode($response);
        exit();
    } else {

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}

if($function=="cashStatus"){

    $refCode=$_POST['refCode'];
    $amount=$_POST['amount'];

    $sql="SELECT `account_from` FROM `transactions` WHERE `ref`='$refCode' and `amount`='$amount'";

    $result=DB::instance()->executeSQL($sql);
    $phoneNumber=$result->fetch_array()[0];
    $count=mysqli_num_rows($result);
    if($count==1){
        $sql="UPDATE `transactions` SET `payment_mode`='M-pesa' where `ref`='$refCode'";
        $result=DB::instance()->executeSQL($sql);
        if($result){
            $message="$amount has been confirmed for reference code. $refCode has been received ";
            sendSMS($phoneNumber,$message);

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "success";
            $response->success = false;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "failed to update status";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "record not available";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if($function=='cheque'){
    //GET IMAGE AND SAVE TO THE FOLDER UPLOADS
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "File is not an image.";
            $response->success = false;
            echo json_encode($response);
            $uploadOk = 0;
        }
    }
    // Check if file already exists
    /*if (file_exists($target_file)) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, file already exists.";
        $response->success = false;
        echo json_encode($response);
        $uploadOk = 0;
    }*/
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, your file is too large.";
        $response->success = false;
        echo json_encode($response);
        $uploadOk = 0;
    }
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Sorry, your Images was not uploaded.";
        $response->success = false;
        echo json_encode($response);
        exit();
        // if everything is ok, try to upload file
    }else{
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

            $link = "https://localhost/payme/uploads/".basename( $_FILES["fileToUpload"]["name"]);
            $refCode=referenceCode();


            $sql="INSERT INTO `images`(`link`, `refCode`) VALUES ('$link','$refCode')";

            $result=DB::instance()->executeSQL($sql);
            if($result){
                $accountTo=$_POST['accountTo'];
                $accountFrom=$_POST['phoneNumber'];
                $amount=$_POST['amount'];
                $refCode=referenceCode();
                $method="slip";
                $status="Pending";

                $sql="INSERT INTO `transactions`(`account_to`, `account_from`, `amount`,`ref`,`payment_mode`,`status`)
              values ('$accountTo','$accountFrom','$amount','$refCode','$method','$status')";

                $result=DB::instance()->executeSQL($sql);
                if($result){
                    $message="cheque recieved for amount $amount reference code $refCode.Once payment is confirmed you will receive a confirmation code";
                    sendSMS($accountFrom,$message);
                    $response = new Response();
                    $response->status = Response::STATUS_SUCCESS;
                    $response->message= "check your phone//n .to complete payments.";
                    $response->success = false;
                    echo json_encode($response);
                    exit();
                }
            }else{
                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->message= "Sorry, payments not updated.";
                $response->success = false;
                echo json_encode($response);
                exit();
            }
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Sorry, there was an error uploading your file.";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }

}




