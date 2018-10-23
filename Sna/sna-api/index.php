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

$function = $_POST['function'];

if ($function =="upload"){

    if($_POST["import"]==="true")
    {
        $email = $_POST['email'];
        $company = $_POST['company'];
        $transactionType = $_POST['transactionType'];

        $temp = explode(".", $_FILES["excel"]["name"]);
        $extension = end($temp); // For getting Extension of selected file
        $allowed_extension = array("xls", "xlsx", "csv"); //allowed extension
        if(in_array($extension, $allowed_extension)) //check selected file extension is present in allowed extension array
        {
            $file = $_FILES["excel"]["tmp_name"]; // getting temporary source of excel file
            include("PHPExcel.php"); // Add PHPExcel Library in this code
            include("PHPExcel/IOFactory.php"); // Add PHPExcel Library in this code
            $objPHPExcel = PHPExcel_IOFactory::load($file); // create object of PHPExcel library by using load() method and in load method define path of selected file

            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet)
            {
                $highestRow = $worksheet->getHighestRow();
                for($row=2; $row<=$highestRow; $row++)
                {
                    $memberId =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                    $name =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                    $phoneNumber = formatPhoneNumber($worksheet->getCellByColumnAndRow(2, $row)->getValue());

                    $sql = '';
                    if($name =='' || $phoneNumber == '') {
                        continue;
                    }
                    if($transactionType=='sms') {
                        $message = $_POST['message'];

                        $sql = "INSERT INTO `uploads`(`memberId`,`name`, `phoneNumber`, `amount`, `company`, `status`,`transactionType`,`message`) 
                                 VALUES ('$memberId','$name','$phoneNumber','0','$company','NotSent','$transactionType','$message')";
                    }else {
                        $amount = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                        $sql = "INSERT INTO `uploads`(`memberId`,`name`, `phoneNumber`, `amount`, `company`, `status`,`transactionType`) 
                                 VALUES ('$memberId','$name','$phoneNumber','$amount','$company','NotSent','$transactionType')";
                    }
                    $result = DB::instance()->executeSQL($sql);
                }
            }
            storeUpdateRecords($company);
            getUploads($company, $transactionType);


        }
        else
        {
        }

    }else{
        $email = $_POST['email'];
        $company = $_POST['company'];
        $transactionType = $_POST['transactionType'];
        $phoneNumbers = $_POST['phoneNumbers'];

        if($transactionType=='sms'){
            $new_array = explode(",", $phoneNumbers);

            $x = 0;

            while (sizeof($new_array) >$x){
                $message = $_POST['message'];
                $phoneNumber = $new_array[$x];
                $sql = "INSERT INTO `uploads`(`memberId`,`name`, `phoneNumber`, `amount`, `company`, `status`,`transactionType`,`message`) 
                                 VALUES ('N/A','N/A','$phoneNumber','0','$company','NotSent','$transactionType','$message')";
                DB::instance()->executeSQL($sql);
                $x++;
            }

        }else{
            //Airtime
            $data = explode("#",$phoneNumbers);

            $amount = $data[0];
            $new_array = explode(",", $data[1]);
            $x = 0;

            while (sizeof($new_array) >$x){
                $message = $_POST['message'];
                $phoneNumber = $new_array[$x];

                $sql = "INSERT INTO `uploads`(`memberId`,`name`, `phoneNumber`, `amount`, `company`, `status`,`transactionType`) 
                                 VALUES ('N/A','N/A','$phoneNumber','$amount','$company','NotSent','$transactionType')";

                DB::instance()->executeSQL($sql);
                $x++;
            }

        }

        storeUpdateRecords($company);
        getUploads($company, $transactionType);



    }
}


function storeUpdateRecords($company){
    //gets records from uploads and merges them to customers table
    $sqlUploads= "SELECT * FROM `uploads` WHERE `company`='$company'";
    $resultUploads = DB::instance()->executeSQL($sqlUploads);

    if ($resultUploads){
        while( $rowUploads = $resultUploads->fetch_assoc()){

            $phoneNumber = formatPhoneNumber($rowUploads['phoneNumber']);
            $memberId =$rowUploads['memberId'];
            $name =$rowUploads['name'];

            $sqlCust= "SELECT * FROM `customers` WHERE `phoneNumber`='$phoneNumber' AND `company`='$company'";
            $resultsCust = DB::instance()->executeSQL($sqlCust);

            if($resultsCust->num_rows<1){

                $sql = "INSERT INTO `customers`( `memberId`, `name`, `phoneNumber`, `company`) 
                            VALUES ('$memberId','$name','$phoneNumber','$company')";
                DB::instance()->executeSQL($sql);
            }
        }


    }



}

if ($function =="uploads"){
    $email =$_POST["email"];
    $company =$_POST['company'];
    $transactionType = $_POST["transactionType"];

    getUploads($company, $transactionType);

}
if ($function =="deleteUploads"){
    $company =$_POST['company'];
    $phoneNumber = $_POST['phoneNumber'];
    $transactionType = $_POST['transactionType'];


    $sql ="DELETE FROM `uploads` WHERE `phoneNumber`='$phoneNumber' AND `company`='$company' AND `transactionType`='$transactionType' AND `status`='NotSent'";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Deleted";
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Failed to delete" ;
        $response->success = false;
        echo json_encode($response);


    }


}if ($function =="deleteContact"){
    $company =$_POST['company'];
    $phoneNumber = $_POST['phoneNumber'];

    $sql ="DELETE FROM `customers` WHERE `phoneNumber`='$phoneNumber' AND `company`='$company' ";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Deleted";
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Failed to delete" ;
        $response->success = false;
        echo json_encode($response);


    }


}

function getUploads($company, $transactionType) {
    $sql ="SELECT * FROM `uploads` WHERE `company`='$company' AND `transactionType` ='$transactionType' AND `status`='NotSent'";

    $result = DB::instance()->executeSQL($sql);
    $message = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =$message->fetch_assoc()['message'] ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = '';
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }
}

if ($function =="transactions"){
    $company = $_POST["company"];
    $transactionType=$_POST["transactionType"];

    $sql ="SELECT * FROM `transaction` WHERE `transactionType` ='$transactionType' AND `company`='$company' ORDER BY id DESC";
    $result = DB::instance()->executeSQL($sql);
    if ($result->num_rows >0){
        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = $result->num_rows;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = $result->num_rows;
        $response->success = false;
        echo json_encode($response);


    }


}

if($function =="submitSMS"){
    $company = $_POST['company'];

    $sql ="SELECT * FROM `customers` WHERE `company`='$company'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        while ($row = $result->fetch_assoc()){
            $memberId = $row['memberId'];
            $name = $row['name'];
            $phoneNumber = $row['phoneNumber'];
            $name = $row['name'];

            $sql = "INSERT INTO `uploads`(`memberId`,`name`, `phoneNumber`, `amount`, `company`, `status`,`transactionType`,`message`) 
                                 VALUES ('$memberId','$name','$phoneNumber','0','$company','NotSent','sms','')";
            DB::instance()->executeSQL($sql);
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message="Initialized , Please go to SMS and send";
        $response->success = true;
        echo json_encode($response);

    }
}

if ($function =="updateNumber"){
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $company =$_POST['company'];

    $sql = "UPDATE `customers` SET `phoneNumber`='$phoneNumber' WHERE `company` ='$company' AND `phoneNumber` ='$phoneNumber'";
    if(  DB::instance()->executeSQL($sql)){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message="Updated";
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message="Failed";
        $response->success = false;
        echo json_encode($response);

    }


}

if ($function =="customers"){
    $company =$_POST['company'];

    $sql ="SELECT * FROM `customers` WHERE `company` ='$company'";
    $result = DB::instance()->executeSQL($sql);
    if ($result->num_rows >0){
        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'No contacts';
        $response->success = false;
        echo json_encode($response);


    }


}

if($function =="register"){
    $username = $_POST['user'];
    $company = $_POST['company'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userType = $_POST['userType'];


    //check if users exits in registered tables
    $check_user = "SELECT * FROM `users` WHERE `email` = '$email'";
    $result = DB::instance()->executeSQL($check_user);

    if ($result->num_rows > 0) {

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Account exists.';
        $response->success = false;

        echo json_encode($response);

    }
    else {
        $sql ="INSERT INTO `users`(`company`,`user`, `email`, `password`,`type`) VALUES ('$company','$username','$email','$password','$userType')";
        $results = DB::instance()->executeSQL($sql);

        if ($results) {
            login($email, $password);
        }
    }

}

if($function =="login"){
    $email = $_POST['email'];
    $password = $_POST['password'];

    login($email, $password);
}

function login ($email, $password) {

    $sql ="SELECT * FROM `users` WHERE `email` ='$email' AND `password` ='$password'";
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
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Please check your credentials and try again. Create an account if you have none ';
        $response->success = false;
        echo json_encode($response);
    }

}
if ($function =="sendSMS"){
    $message = $_POST["message"];
    $transactionType = "sms";
    $senderId = $_POST['senderId'];
    $company =$_POST['company'];

    $sql ="SELECT * FROM `uploads` WHERE `company`='$company' AND `status` ='NotSent' AND `transactionType`='$transactionType'";

    $result = DB::instance()->executeSQL($sql);
    if ($result->num_rows >0){
        while( $row = $result->fetch_assoc()){
            $phoneNumber =formatPhoneNumber($row['phoneNumber']);
            $memberId = $row['memberId'];
            $name = $row['name'];
            if(accountBalance($company) > 2){
                sendSMS($memberId,$name,$phoneNumber,$message,$company, $senderId);
            }

        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= "Done";
        $response->success = true;
        echo json_encode($response);


    }else{

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'No Transactions';
        $response->success = false;
        echo json_encode($response);


    }
}

if($function =="sendAirtime"){
    $transactionType = "airtime";
    $company =$_POST['company'];

    $sql ="SELECT * FROM `uploads` WHERE `company`='$company' AND `status` ='NotSent' AND `transactionType`='$transactionType'";


    $result = DB::instance()->executeSQL($sql);
    if ($result->num_rows >0){
        while( $row = $result->fetch_assoc()){
            $phoneNumber =$row['phoneNumber'];
            $amount =$row['amount'];
            $memberId = $row['memberId'];
            $name =$row['name'];
            if(accountBalance($company) >= $amount){
                airTime($memberId,$name,$phoneNumber,$amount,$company);
                DB::instance()->executeSQL($sql);
            }

        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= "Done";
        $response->success = true;
        echo json_encode($response);


    }else{

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'No Transactions';
        $response->success = false;
        echo json_encode($response);


    }
}

if($function =="sendAirtimeDirect"){
    $phoneNumber = $_POST['phoneNumber'];
    $amount = $_POST['amount'];

    $username = "Nouveta";
    $apiKey   = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";
    // NOTE: If connecting to the sandbox, please use your sandbox login credentials
    //Specify the phone number/s and amount in the format shown
    //Example shown assumes we want to send KES 100 to two numbers
    // Please ensure you include the country code for phone numbers (+254 for Kenya in this case)
    // Please ensure you include the country code for phone numbers (KES for Kenya in this case)

    $recipients = array(
        array("phoneNumber"=>$phoneNumber, "amount"=>"KES $amount")
    );

    //Convert the recipient array into a string. The json string produced will have the format:
    // [{"amount":"KES 100", "phoneNumber":"+254711XXXYYY"},{"amount":"KES 100", "phoneNumber":"+254733YYYZZZ"}]
    //A json string with the shown format may be created directly and skip the above steps
    $recipientStringFormat = json_encode($recipients);

    //Create an instance of our awesome gateway class and pass your credentials
    $gateway = new AfricasTalkingGateway($username, $apiKey);
    // NOTE: If connecting to the sandbox, please add the sandbox flag to the constructor:
    /*************************************************************************************
     ****SANDBOX****
    $gateway    = new AfricasTalkingGateway($username, $apiKey, "sandbox");
     **************************************************************************************/
    // Thats it, hit send and we'll take care of the rest. Any errors will
    // be captured in the Exception class as shown below

    try {
        $results = $gateway->sendAirtime($recipientStringFormat);
        foreach($results as $result) {
            /*  echo $result->status;
              echo $result->amount;
              echo $result->phoneNumber;
              echo $result->discount;
              echo $result->requestId;

              //Error message is important when the status is not Success
              echo $result->errorMessage;*/
            /*  if($result->status =='Sent') {
                  $amount = str_replace("KES","","$result->amount");

                  $sql = "INSERT INTO `transaction`(`memberId`,`name`,`phoneNumber`, `amount`, `transactionType`,  `status`, `company`,`message`,`code`) VALUES
                                           ('$memberId','$name','$result->phoneNumber','$amount','airtime','Sent','$email','$result->errorMessage','$result->requestId')";
                  DB::instance()->executeSQL($sql);

                  $sqll = "UPDATE `uploads` SET `status`='sent' WHERE `phoneNumber` ='$phoneNumber' AND `company`='$email' AND `transactionType` ='airtime'";
                  DB::instance()->executeSQL($sqll);
              }*/


        }
    }
    catch(AfricasTalkingGatewayException $e){
        echo $e->getMessage();
    }

}

if($function =='accountBalance'){
    $company =$_POST['company'];

    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->message = 'Balance';
    $response->data = "Balance: KES ".accountBalance($company);
    $response->success = true;
    echo json_encode($response);

}

function accountBalance($accountNumber) {
//    $accountNumber = preg_replace('/[^\dxX]/', '', $accountNumber);

    $sqlTotalCredit = "SELECT SUM(amount)
                       FROM `transaction`
                       WHERE company = '$accountNumber' AND entry = 'Cr'";

    $sqlTotalDebit = "SELECT SUM(amount)
                      FROM `transaction`
                      WHERE company = '$accountNumber' AND entry = 'Dr'";

    $resultsTotalCredit = DB::instance()->executeSQL($sqlTotalCredit);
    $resultsTotalDebit = DB::instance()->executeSQL($sqlTotalDebit);

    if ($resultsTotalCredit)
        if ($resultsTotalDebit)
            return $resultsTotalCredit->fetch_array()[0]-$resultsTotalDebit->fetch_array()[0];

}


/**
 * @param $email
 * @param $amount
 */
function accountTopup($company, $amount,$code,$phoneNumber)
{
    $sql = "SELECT * FROM `users`  WHERE `company`='$company'";
    $result = DB::instance()->executeSQL($sql)->fetch_assoc();
    $name = $result['user'];
    $status = $result['status'];

    $sqll = "INSERT INTO `transaction`(`code`,`memberId`,`name`,`phoneNumber`, `amount`, `transactionType`,  `status`, `company`,`message`,`entry`) VALUES
                                     ('$code','$company','$name','$phoneNumber','$amount','topUp','$status','$company','Account Top','Cr')";
    if (DB::instance()->executeSQL($sqll)) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'success';
        $response->data = "Balance: KES " . accountBalance($company);
        $response->success = true;
        echo json_encode($response);

    } else {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Failed';
        $response->data = "Balance: KES " . accountBalance($company);
        $response->success = false;
        echo json_encode($response);

    }
}

if($function == "accountTop"){
    $email = $_POST['email'];
    $amount = $_POST['amount'];

    accountTopup($email, $amount,confirmatioCode(),'');

}


function sendSMS($memberId,$name,$phoneNumber,$message,$email, $senderId){
    //Sending Messages using sender id/short code

    $username   = 'Nouveta';
    /* $apikey     = "d4f3740dd4a07bbaf3b366d9ca1917c5d7992d56bdce8b404993f397879beece";*/
    $apikey     = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";

// Specify your AfricasTalking shortCode or sender id
    $from = $senderId;
    $gateway    = new AfricasTalkingGateway($username, $apikey);
    try
    {
        $results = $gateway->sendMessage($phoneNumber, $message, $from);

        foreach($results as $result) {

            if($result->status=='Success') {
                //  $amount = str_replace("KES","","$result->cost");
                $amount = '1';

                $sql = "INSERT INTO `transaction`(`memberId`,`name`,`phoneNumber`, `amount`, `transactionType`,  `status`, `company`,`message`,`code`) VALUES
                                         ('$memberId','$name','$result->number','$amount','sms','$result->status','$email','$message','$result->requestId')";
                DB::instance()->executeSQL($sql);

                $sqll = "UPDATE `uploads` SET `status`='sent' WHERE `phoneNumber` ='$phoneNumber' AND `company`='$email' AND `transactionType` ='sms'";
                DB::instance()->executeSQL($sqll);

            }

        }
    }
    catch ( AfricasTalkingGatewayException $e )
    {
        echo "Encountered an error while sending: ".$e->getMessage();
    }

}
function sendSMS2($memberId,$name,$phoneNumber,$message,$email, $senderId){
    //Sending Messages using sender id/short code

    $username   = 'wanjalaalex25';
    $apikey     = "d4f3740dd4a07bbaf3b366d9ca1917c5d7992d56bdce8b404993f397879beece";
    /* $apikey     = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";*/

// Specify your AfricasTalking shortCode or sender id
    $from = $senderId;
    $gateway    = new AfricasTalkingGateway($username, $apikey);
    try
    {
        $results = $gateway->sendMessage($phoneNumber, $message, $from);

        foreach($results as $result) {

            if($result->status=='Success') {

            }

        }
    }
    catch ( AfricasTalkingGatewayException $e )
    {
        echo "Encountered an error while sending: ".$e->getMessage();
    }

}

if($function=="sendDataSms"){
    $message = $_POST["message"];
    $transactionType = "sms";
    $senderId = 'NICOZA';
    $company ='boeyalex@gmail.com';
    $memberId = "NA";
    $phoneNumber= $_POST['phoneNumber'];

    sendSMS2($memberId,'NA',$phoneNumber,$message,$company, $senderId);

}

function airTime($memberId,$name,$phoneNumber,$amount,$email){

    $username = "Nouveta";
    $apiKey   = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";
    // NOTE: If connecting to the sandbox, please use your sandbox login credentials
    //Specify the phone number/s and amount in the format shown
    //Example shown assumes we want to send KES 100 to two numbers
    // Please ensure you include the country code for phone numbers (+254 for Kenya in this case)
    // Please ensure you include the country code for phone numbers (KES for Kenya in this case)

    $recipients = array(
        array("phoneNumber"=>$phoneNumber, "amount"=>"KES $amount")
    );

    //Convert the recipient array into a string. The json string produced will have the format:
    // [{"amount":"KES 100", "phoneNumber":"+254711XXXYYY"},{"amount":"KES 100", "phoneNumber":"+254733YYYZZZ"}]
    //A json string with the shown format may be created directly and skip the above steps
    $recipientStringFormat = json_encode($recipients);

    //Create an instance of our awesome gateway class and pass your credentials
    $gateway = new AfricasTalkingGateway($username, $apiKey);
    // NOTE: If connecting to the sandbox, please add the sandbox flag to the constructor:
    /*************************************************************************************
     ****SANDBOX****
    $gateway    = new AfricasTalkingGateway($username, $apiKey, "sandbox");
     **************************************************************************************/
    // Thats it, hit send and we'll take care of the rest. Any errors will
    // be captured in the Exception class as shown below

    try {
        $results = $gateway->sendAirtime($recipientStringFormat);
        foreach($results as $result) {
            /*  echo $result->status;
              echo $result->amount;
              echo $result->phoneNumber;
              echo $result->discount;
              echo $result->requestId;

              //Error message is important when the status is not Success
              echo $result->errorMessage;*/
            if($result->status =='Sent') {
                $amount = str_replace("KES","","$result->amount");

                $sql = "INSERT INTO `transaction`(`memberId`,`name`,`phoneNumber`, `amount`, `transactionType`,  `status`, `company`,`message`,`code`) VALUES
                                         ('$memberId','$name','$result->phoneNumber','$amount','airtime','Sent','$email','$result->errorMessage','$result->requestId')";
                DB::instance()->executeSQL($sql);

                $sqll = "UPDATE `uploads` SET `status`='sent' WHERE `phoneNumber` ='$phoneNumber' AND `company`='$email' AND `transactionType` ='airtime'";
                DB::instance()->executeSQL($sqll);
            }


        }
    }
    catch(AfricasTalkingGatewayException $e){
        echo $e->getMessage();
    }



}

if($function =="senderId"){
    $userId = $_POST['userId'];
    $senderId = $_POST['senderId'];

    $sql ="INSERT INTO `senderid`( `userId`, `senderId`) 
                                VALUES ('$userId','$senderId')";
    if(DB::instance()->executeSQL($sql)){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Record created';
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Failed';
        $response->success = false;
        echo json_encode($response);

    }
}

if($function =="deleteSenderId"){
    $userId = $_POST['userId'];
    $senderId = $_POST['senderId'];

    $sql ="DELETE FROM `senderid` WHERE `company`='$userId' AND `senderId` = '$senderId'";
    if(DB::instance()->executeSQL($sql)){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Record deleted';
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Failed';
        $response->success = false;
        echo json_encode($response);

    }
}


if($function =="updateStatus"){
    $userId = $_POST['userId'];

    $sql ="SELECT * FROM `users` WHERE `email` ='$userId'";
    $result = DB::instance()->executeSQL($sql)->fetch_assoc();
    if($result['status']=="Approved"){
        if(DB::instance()->executeSQL("UPDATE `users` SET `status`= 'NotApproved' WHERE `email` ='$userId'")){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'Approved';
            $response->success = true;
            echo json_encode($response);

        }

    }
    if($result['status']=="NotApproved"){
        if( DB::instance()->executeSQL("UPDATE `users` SET `status`= 'Approved' WHERE `email` ='$userId'")){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = 'NotApproved';
            $response->success = true;
            echo json_encode($response);

        }

    }
}



if($function == "users") {

    //  $sql = "SELECT * FROM `users` WHERE `type` = 'admin'";
    $sql = "SELECT * FROM `users` ";
    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){
        while( $row = $result->fetch_assoc()){
            $row['accountBalance'] = "KES ".accountBalance($row['email']);
            $row['senderIds'] = getSenderIds($row['email']);
            $row['senderId'] = '';
            $new_array[] = $row;
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'No users';
        $response->success = false;
        echo json_encode($response);


    }

}

if($function =="senderIds"){
    $company = $_POST["company"];

    $senderIds = getSenderIds($company);

    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->data= $senderIds;
    $response->success = true;
    echo json_encode($response);


}if($function =="addSenderId"){
    $company = $_POST["company"];
    $senderId = $_POST['senderId'];

    $sql ="INSERT INTO `senderid`(`company`, `senderId`) VALUES ('$company','$senderId')";
    $result =  DB::instance()->executeSQL($sql);
    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->success = false;
        echo json_encode($response);

    }


}

if($function =="callback"){
    $amount = $_POST['amount'];
    $phoneNumber = $_POST['phoneNumber'];
    $code = $_POST['code'];

    $sql = "SELECT * FROM `holdings` WHERE `code` ='$code'";
    $result = DB::instance()->executeSQL($sql);
    if($result->num_rows > 0){
        $email = $result->fetch_assoc()['email'];
        accountTopup($email, $amount,$code,$phoneNumber);
    }
}


if($function =="mpesaPush"){
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];
    $amount = $_POST["amount"];
    $company = $_POST['company'];

    $curl = curl_init();
    $code = confirmatioCode();

    DB::instance()->executeSQL("INSERT INTO `holdings`(`code`, `amount`, `phoneNumber`, `email`) VALUES ('$code','$amount','$phoneNumber','$company')");

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://62.12.114.194/nou/mprocess.php?amt=".$amount."&phone=".$phoneNumber."&ref_id=".$code,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
        ),
    ));

    $result = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $err;
        $response->success = false;
        echo json_encode($response);


    } else {
        echo  $result;


    }

}
function getSenderIds($company) {
    $sql = "SELECT * FROM `senderid` WHERE `company` = '$company'";
    $result = DB::instance()->executeSQL($sql);

    $new_array = [];
    if ($result->num_rows >0) {
        while ($row = $result->fetch_assoc()) {
            $new_array[] = $row; // Inside while loop
        }
        return $new_array;
    } else {
        return [];
    }
}

if($function =="generatePDF"){
    $email = $_POST['email'];
    $company = $_POST['company'];
    $transactionType = $_POST['transactionType'];

    $result = DB::instance()->executeSQL("SELECT * FROM `transaction`  WHERE `company` ='$company' AND `transactionType` ='$transactionType'");
    $header = DB::instance()->executeSQL("SELECT `COLUMN_NAME` 
FROM `INFORMATION_SCHEMA`.`COLUMNS` 
WHERE `TABLE_SCHEMA`='blog_samples' 
AND `TABLE_NAME`='toy'");


    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',5);
    foreach($header as $heading) {
        foreach($heading as $column_heading)
            $pdf->Cell(20,6,$column_heading,1);
    }
    foreach($result as $row) {
        $pdf->SetFont('Arial','',5);
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(20,6,$column,1);
    }
    $pdf->Output();
}

if($function =="mail"){
    $email = $_POST['email'];

    $to = $email;
    $subject = "Test mail";
    $message = "Hello! This is a simple email message.";
    $from = "someonelse@example.com";
    $headers = "From: $from";
    mail($to,$subject,$message,$headers);
    echo "Mail Sent.";
}
function confirmatioCode(){
    $ConfCode = new ConfirmationCode;
    $code = $ConfCode->auto(4);
    return $code;
}

if($function =="test") {

    echo formatPhoneNumber($_POST['phoneNumber']);

}

function formatPhoneNumber($phoneNumber) {
    $phoneNumber = preg_replace('/[^\dxX]/', '', $phoneNumber);
    $phoneNumber = preg_replace('/^0/','254',$phoneNumber);

    $phoneNumber = $phone = preg_replace('/\D+/', '', $phoneNumber);

    return $phoneNumber;
}

?>