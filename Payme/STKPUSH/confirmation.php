<?php
/**
 * This is the callback  from safaricom for direct payments
 *
 */

include 'includes/DB.php';
include 'helpers/Response.php';
include 'helpers/ConfirmationCode.php';
include "helpers/AfricasTalkingGateway.php";
include 'helpers/fpdf/fpdf.php';
/*{ "TransactionType": "", "TransID": "LKM8BLNG3Y", "TransTime": "20171122171048", "TransAmount": "10.00", "BusinessShortCode": "175555", "BillRefNumber": "tes", "InvoiceNumber": "", "OrgAccountBalance": "46246.00", "ThirdPartyTransID": "", "MSISDN": "254719401837", "FirstName": "ALEX", "MiddleName": "", "LastName": "" }*/

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");

    $data = file_get_contents('php://input');
    $json = json_decode($data);

    $PayBillNumber = $json->BusinessShortCode;
    $PhoneNumber = $json->MSISDN;
    $Amount = $json->TransAmount;
    $AccountReference = $json->BillRefNumber;
    $MpesaReceiptNumber = $json->TransID;
    $OrgAccountBalance = $json->OrgAccountBalance;
    $TransTime = $json->TransTime;


sleep(30);
confirmation($AccountReference,$PhoneNumber,$Amount,$MpesaReceiptNumber,$TransTime);


function confirmation($AccountReference,$PhoneNumber,$Amount,$MpesaReceiptNumber,$TransTime){
    //checkIfCallBack has been updated
    $sql ="SELECT * FROM `push_request` WHERE `account_reference`='$AccountReference' AND `callback_returned`='2'";
    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows>0){
        //Update push_request status to 1
        $sql ="UPDATE `push_request` SET `callback_returned`='1' WHERE `account_reference` ='$AccountReference'";
        DB::instance()->executeSQL($sql);

        //Get payBill and Channel callback Request
        $sql = "SELECT * FROM `push_request` WHERE `account_reference`='$AccountReference'";
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

                callBackToClientsPOST($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$AccountReference,$TransTime);
                callBackToClientsGET($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$AccountReference,$TransTime);
                saveTransaction($PayBillNumber,$PhoneNumber,$Amount,$AccountReference,$MpesaReceiptNumber,"MPESA",$company,'success',$merchant_request_id);
            }


            echo "success";

        }
    }else{
        echo "Callback already returned for $AccountReference";
    }

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
function callBackToClientsPOST($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$TransactionDesc,$TransTime){


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    curl_setopt_array($curl, array(
        CURLOPT_URL => "$callback",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PayBillNumber\"\r\n\r\n$PayBillNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PhoneNumber\"\r\n\r\n$PhoneNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"MpesaReceiptNumber\"\r\n\r\n$MpesaReceiptNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"Amount\"\r\n\r\n$Amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"AccountReference\"\r\n\r\n$AccountReference\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionDesc\"\r\n\r\n$TransactionDesc\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransTime\"\r\n\r\n$TransTime\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
            "postman-token: 4acc7314-6afb-389d-e60d-efa1d37e9456"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo $response;
    }

}
function callBackToClientsGET($PayBillNumber,$callback,$PhoneNumber,$MpesaReceiptNumber,$Amount,$AccountReference,$TransactionDesc,$TransTime){

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    curl_setopt_array($curl, array(
        CURLOPT_URL => "$callback?AccountReference=$AccountReference&PhoneNumber=$PhoneNumber&TransTime=$TransTime&Amount=$Amount&MpesaReceiptNumber=$MpesaReceiptNumber&PayBillNumber=$PayBillNumber&TransactionDesc=$TransactionDesc",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
            "postman-token: f6976f62-0979-a204-6183-486ccff84b66"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo $response;
    }
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