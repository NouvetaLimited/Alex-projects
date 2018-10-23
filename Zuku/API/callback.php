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

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");

$order_number = $_POST['AccountReference'];
$phone_number = formatPhoneNumber( $_POST['PhoneNumber']);
$amount_paid = $_POST['Amount'];
$code = $_POST['MpesaReceiptNumber'];
$payment_method = "MPESA";



$sql ="INSERT INTO `transactions`(`phone_number`, `amount_paid`, `code`, `order_number`, `payment_method`)
                       VALUES ('$phone_number','$amount_paid','$code','$order_number','$payment_method')";

$result = DB::instance()->executeSQL($sql);
if($result){

    $sql ="SELECT * FROM `ticket_sales` WHERE `order_number`='$order_number'";
    $result = DB::instance()->executeSQL($sql);
    if($result){
        $paidValue =accountBalance($order_number);
        $expectedValue = $result->fetch_assoc()['amount'];
        if($paidValue>=$expectedValue){
            //update the status of the tickets
            $sql = "UPDATE `ticket_sales` SET `paid` ='1' WHERE `order_number`='$order_number'";
            $result = DB::instance()->executeSQL($sql);
            //send tickets
            $sql ="SELECT * FROM `tickets` WHERE `order_number` ='$order_number'";
            $result = DB::instance()->executeSQL($sql);
            while ($row =$result->fetch_assoc()){
                $ticket_number = $row['ticket_number'];
                $link = "Follow link to download your ticket https://ticketsoko.nouveta.co.ke/ticket.html?ticket_number=$ticket_number";
                sendSMS($phone_number,$link);
            }

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Tickets Sent";
            $response->success = true;
            echo json_encode($response);
            exit();


        }else{
            $balance = $expectedValue-$paidValue;
            sendSMS($phone_number,"Please complete your payment in order to receive your ticket(s) Paid KES. $paidValue expected KES.$expectedValue . Balance KES.$balance");
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Tickets  not Sent Pay full the amount";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }
}


function confirmatioCode(){
    $ConfCode = new ConfirmationCode;
    $code = $ConfCode->auto(4);
    return $code;
}


function formatPhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^\dxX]/', '', $phoneNumber);
    $phoneNumber = preg_replace('/^0/','254',$phoneNumber);

    $phoneNumber = $phone = preg_replace('/\D+/', '', $phoneNumber);

    return $phoneNumber;
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

function accountBalance($order_number) {

    $sqlTotal = "SELECT SUM(amount_paid)
                       FROM `transactions`
                       WHERE order_number = '$order_number'";

    $results = DB::instance()->executeSQL($sqlTotal);

    if ($results)
        return $results->fetch_array()[0];

}


?>