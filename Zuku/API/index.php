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

$function = $_REQUEST['function'];

if($function =="uploadExcel"){
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
                $name =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                $accountNumber =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $phoneNumber = formatPhoneNumber($worksheet->getCellByColumnAndRow(2, $row)->getValue());
                $amount =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $user =  $worksheet->getCellByColumnAndRow(4, $row)->getValue();

                $sql = "INSERT INTO `billing`(`name`,`account_number`, `mobile_number`, `amount`,`user`) 
                                         VALUES ('$name','$accountNumber','$phoneNumber','$amount','$user')";
                $result = DB::instance()->executeSQL($sql);
            }
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Upload Success";
        $response->success = true;
        echo json_encode($response);


    }
    else
    {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Invalid file";
        $response->success = false;
        echo json_encode($response);
    }

}

if ($function =="getBillings"){
    $keyword = $_POST['keyword'];
    $status = $_POST['status'];
    $user =$_POST['user'];


    if(empty($keyword)){
        $sql ="SELECT * FROM `billing` WHERE `status` = '$status' AND `user`='$user'";
    }else{

        $sql ="SELECT * FROM `billing` WHERE  `name` LIKE '%$keyword%' OR `account_number` LIKE '%$keyword%' OR `mobile_number` LIKE '%$keyword%' OR `amount` LIKE '%$keyword%'  OR `mpesa_code` LIKE '%$keyword%' AND `status` = '$status' AND `user`='$user'";
    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Records found" ;
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

if($function==="zukuCallback"){

    $accountNumber = $_POST['AccountReference'];
    $MpesaReceiptNumber =$_POST['MpesaReceiptNumber'];
    $amount_paid = $_POST['amount'];

    if($MpesaReceiptNumber==="FAILED"){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="$MpesaReceiptNumber";
        $response->success = false;
        echo json_encode($response);

    }else{
       // $sql ="UPDATE `billing` SET `status`='paid', `amount_paid`='$amount_paid', `mpesa_code`='$MpesaReceiptNumber' WHERE `account_number` ='$accountNumber'";
        $sql ="DELETE FROM `billing` WHERE `account_number`='$accountNumber'";
        $result = DB::instance()->executeSQL($sql);
        if($result){

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message ="success";
            $response->success = true;
            echo json_encode($response);

        }else{

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message ="failed";
            $response->success = false;
            echo json_encode($response);

        }

    }

}

if($function==="createBillings"){

    $sql = "INSERT INTO `billing`(`name`,`account_number`, `mobile_number`, `amount`) 
                         VALUES ('name','accountNumber','phoneNumber','amount')";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="success";
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="failed to create";
        $response->success = false;
        echo json_encode($response);
    }
}

if($function==="pay"){

    $Amount = $_POST['amount'];
    $PhoneNumber = $_POST['phoneNumber'];
    $AccountReference = $_POST['accountNumber'];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://payme.nouveta.co.ke/api/index.php",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionType\"\r\n\r\nCustomerPayBillOnline\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PayBillNumber\"\r\n\r\n256666\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"Amount\"\r\n\r\n$Amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"PhoneNumber\"\r\n\r\n$PhoneNumber\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"AccountReference\"\r\n\r\n$AccountReference\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"TransactionDesc\"\r\n\r\nZUKU\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer UVZoQk9rcFhDcWxlR1RPSnJBa1RaRkZQSjdZdlVSRzdZTThHVldLUU1jZz06MTIzNDU6UTIydkozVll4RzFMWUV2MkViSDl5UVN3NFFRanZrNVJoVThQM0pXTXRIRT06MTk3LjI0OC4xNDkuNjI6MDQvMDAvMTcgMTIwMA==",
            "Cache-Control: no-cache",
            "Postman-Token: 496bc8ee-896e-302d-31c4-54d106910842",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        //  echo "cURL Error #:" . $err;
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =$err;
        $response->success = false;
        echo json_encode($response);
    } else {
        // echo $response;
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Send";
        $response->success = true;
        echo json_encode($response);
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

if($function=="chatData"){

    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->message ="totalUploadsCount : totalUploadsAmount : totalPaidCount : totalPaidAmount";
    $response->data =totalUploadsCount().":".totalUploadsAmount().":".totalPaidCount().":".totalPaidAmount();
    $response->success = true;
    echo json_encode($response);

}
if($function=="login"){
    $user = $_POST['username'];
    $password = $_POST['password'];

    $sql ="SELECT * FROM `users` WHERE `user_name` ='$user' AND `password` ='$password'";
    $result = DB::instance()->executeSQL($sql);
    if($result->num_rows >0){

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="success";
        $response->data= $result->fetch_assoc()['user_name'];
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Invalid credentials";
        $response->success = false;
        echo json_encode($response);
    }
}

if($function=="createUsers"){
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    $sql ="INSERT INTO `users`(`user_name`, `password`) VALUES ('$user_name','$password')";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Created Success";
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="failed to create";
        $response->success = false;
        echo json_encode($response);
    }
}
if($function=="deleteUsers"){

    $id = $_POST['id'];
    $sql ="DELETE FROM `users` WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Delete";
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="failed to delete";
        $response->success = false;
        echo json_encode($response);
    }
}
if($function=="readUsers"){

    $result = DB::instance()->executeSQL("SELECT * FROM `users`");

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message ="Records found" ;
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

function totalUploadsCount(){
//count uploads
    $sql ="SELECT * FROM `billing` ";
    $result = DB::instance()->executeSQL($sql);

    return $result->num_rows;

}
function totalUploadsAmount(){
//sum total uploads amount
    $sqlTotal = "SELECT SUM(amount)
                       FROM `billing`";

    $results = DB::instance()->executeSQL($sqlTotal);

    if ($results)
        return $results->fetch_array()[0];
}
function totalPaidCount(){
//sum all paid count
    $sql ="SELECT * FROM `billing` WHERE `status`='paid'";
    $result = DB::instance()->executeSQL($sql);

    return $result->num_rows;
}
function totalPaidAmount(){
//sum all total paid
    $sqlTotal = "SELECT SUM(amount)
                       FROM `billing` WHERE `status`='paid'";

    $results = DB::instance()->executeSQL($sqlTotal);

    if ($results)
        return $results->fetch_array()[0];

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