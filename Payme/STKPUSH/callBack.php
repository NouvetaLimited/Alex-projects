<?php
/**
 * This is the callback for STK PUSH
 */

include 'includes/DB.php';
include 'helpers/Response.php';
include 'helpers/ConfirmationCode.php';
include "helpers/AfricasTalkingGateway.php";
include 'helpers/fpdf/fpdf.php';

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");

$data = file_get_contents('php://input');
//sendMessage($data,'NOUVETA','0715815485');
$json = json_decode($data);
$Body = $json->Body;
$stkCallback = $Body->stkCallback;
$MerchantRequestID = $stkCallback->MerchantRequestID;
$ResultCode = $stkCallback->ResultCode;
$CallbackMetadata =$stkCallback->CallbackMetadata;
$Item = $CallbackMetadata->Item;
$Item = json_decode(json_encode($Item),true);

$Amount = json_decode(json_encode($Item[0]));;;
$MpesaReceiptNumber  =json_decode(json_encode($Item[1]));
$PhoneNumber = json_decode(json_encode($Item[4]));

$PhoneNumber =$PhoneNumber->Value;
$MpesaReceiptNumber = $MpesaReceiptNumber->Value;
$Amount = $Amount->Value;



//checkIfCallBack has been updated
$sql ="SELECT * FROM `push_request` WHERE `merchant_request_id`='$MerchantRequestID' AND `callback_returned`='0'";
$result = DB::instance()->executeSQL($sql);

if($result->num_rows>0){
    if($ResultCode==0){
        //Update push_request status to 1
        $sql ="UPDATE `push_request` SET `callback_returned`='1' WHERE `merchant_request_id` ='$MerchantRequestID'";
        DB::instance()->executeSQL($sql);
    }else{
        //Update push_request status to 1
        $sql ="UPDATE `push_request` SET `callback_returned`='2' WHERE `merchant_request_id` ='$MerchantRequestID'";
        DB::instance()->executeSQL($sql);
    }

    //Get payBill and Channel callback Request
    $sql = "SELECT * FROM `push_request` WHERE `merchant_request_id`='$MerchantRequestID'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $res = new Response();
        $res->data = $result->fetch_assoc();
        $PayBillNumber =  $res->data['paybill_number'];
        $AccountReference =$res->data['account_reference'];
        $TransactionDesc=$res->data['TransactionDesc'];
        $mobileNotification = $res->data['mobileNotification'];
        $phone =  $res->data['phone'];
        $pushAmount =  $res->data['amount'];
        $merchant_request_id = $res->data['merchant_request_id'];


        //check configurations details
        $sql ="SELECT * FROM `configuration` WHERE `short_code`='$PayBillNumber'";
        $result = DB::instance()->executeSQL($sql);

        $response = new Response();
        $response->data = $result->fetch_assoc();

        if($result){

            //ROUTE THE  RESPONSE
            $callback = null;
            if($response->data['callback']==="MULTIPLE"){
                $sql ="SELECT * FROM `route` WHERE `destination` ='$TransactionDesc'";
                $result = DB::instance()->executeSQL($sql);
                if($result){
                    $callback = $result->fetch_assoc()['url'];
                }
            }else{
                $callback = $response->data['callback'];
            }

            $sms = $response->data['sms'];
            $message =$response->data['message'];
            $sender_id =$response->data['sender_id'];
            $tariff =$response->data['tariff'];
            $tariff_value =$response->data['tariff_value'];
            $company =$response->data['company'];

            //autoBilling Configurations
            if($tariff==='1'){
                //bill by value
                //TODO
                autoBilling();

            } if($tariff==='2'){
                //bill by percentage
                //TODO
                autoBilling();
            }


            if($ResultCode==0){
                callBackToClients($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$TransactionDesc);
                saveTransaction($PayBillNumber,$PhoneNumber,$Amount,$AccountReference,$MpesaReceiptNumber,"MPESA",$company,'success',$merchant_request_id);

                //if sms true send sms
                if($sms==='1'){
                    //TODO CHECK SNA BALANCE
                    sendMessage($message,$sender_id,$PhoneNumber);
                    $message2 = "KES. ".$Amount." received from ".$PhoneNumber;
                    if($mobileNotification==='NULL'){
                        //TODO
                    }else{
                        sendMessage($message2,$sender_id,$mobileNotification);
                    }

                }

            }else{
                callBackToClients($PayBillNumber,$callback,$PhoneNumber,"FAILED",$Amount,$AccountReference,$TransactionDesc);
                saveTransaction($PayBillNumber,$phone,$pushAmount,$AccountReference,'',"MPESA",$company,'failed',$merchant_request_id);
                // sendMessage("User cancel the request",$sender_id,"0719401837");
            }

            objectCallbackToClients($data,$callback);

        }


        echo "success";

    }
}else{
    echo "Callback already returned for $MerchantRequestID";
}

function saveTransaction($paybill,$phone_number,$amount,$ref,$transaction_code,$payment_mode,$company, $status,$merchant_request_id){

    //CHECK IF TRANSACTION EXIST
    $sql = "SELECT * FROM `transactions` WHERE `account_from`='$phone_number' AND `transaction_code`='$transaction_code'";
    $result = DB::instance()->executeSQL($sql);
    if($result->num_rows>0){
        exit();
    }else{

        $sql ="INSERT INTO `transactions`(`account_to`, `account_from`, `amount`, `ref`, `transaction_code`,`payment_mode`,`company`,`status`,`merchant_request_id`) 
                              VALUES ('$paybill','$phone_number','$amount','$ref','$transaction_code','$payment_mode','$company','$status','$merchant_request_id')";
        DB::instance()->executeSQL($sql);
    }


}

function autoBilling(){
    //TODO

}

function callBackToClients($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$TransactionDesc)
{
    $TransactionType ="callback";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "$callback",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PayBillNumber\"\r\n\r\n$PayBillNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PhoneNumber\"\r\n\r\n$PhoneNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"MpesaReceiptNumber\"\r\n\r\n$MpesaReceiptNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"Amount\"\r\n\r\n$Amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"AccountReference\"\r\n\r\n$AccountReference\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionDesc\"\r\n\r\n$TransactionDesc\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransTime\"\r\n\r\n20171122171048\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionType\"\r\n\r\n$TransactionType\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Postman-Token: a35c6ce3-4e32-fce7-93a0-1972acca5611",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    /*if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo $response;
    }*/

    /*$curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "$callback",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PayBillNumber\"\r\n\r\n$PayBillNumber\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PhoneNumber\"\r\n\r\n$PhoneNumber\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"MpesaReceiptNumber\"\r\n\r\n$MpesaReceiptNumber\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"Amount\"\r\n\r\n$Amount\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"AccountReference\"\r\n\r\n$AccountReference\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionDesc\"\r\n\r\n$TransactionDesc\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionType\"\r\n\r\n$TransactionType\r\n
                               ------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
            "postman-token: 75f160bb-78df-affc-460d-877e5e4652fa"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);*/

}
function objectCallbackToClients($data,$callback){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8800",
            CURLOPT_URL => "$callback",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "$data",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "Postman-Token: 621d4be8-09bb-4bce-9170-9bf1d09efd82"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


}
function sendMessage($message , $sender_id,$phoneNumber){
    $username   = "Nouveta";
    $apikey     = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";

    $gateway    = new AfricasTalkingGateway($username, $apikey);

    try
    {
        // Thats it, hit send and we'll take care of the rest.
        $results = $gateway->sendMessage($phoneNumber, $message,$sender_id);

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

