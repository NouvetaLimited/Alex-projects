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


function accountDetails($accountNumber,$purpose){
    if($purpose==="exits"){
        $sql ="SELECT * FROM `farmers` WHERE `farmerPhoneNumber` = '$accountNumber'";
        $result = DB::instance()->executeSQL($sql);
        if($result->num_rows >0){
            return $result->num_rows;
        }else{
            $sql ="SELECT * FROM `clients` WHERE `clientPhoneNumber` = '$accountNumber'";
            $result = DB::instance()->executeSQL($sql);
            if($result->num_rows > 0){
                return $result->num_rows;
            }else{
                $sql ="SELECT * FROM `productscategories` WHERE `productCategoryId` = '$accountNumber'";
                $result = DB::instance()->executeSQL($sql);
                if($result ->num_rows > 0){
                    return $result ->num_rows;
                }else{
                    $sql ="SELECT * FROM `agents` WHERE `email` = '$accountNumber'";
                    $result = DB::instance()->executeSQL($sql);
                    if($result ->num_rows > 0){
                        return $result ->num_rows;
                    }else{
                        $sql ="SELECT * FROM `users` WHERE `email` = '$accountNumber'";
                        $result = DB::instance()->executeSQL($sql);
                        if($result ->num_rows > 0){
                            return $result ->num_rows;
                        }else
                        {
                            return 0;
                        }
                    }
                }
            }

        }

    }
return null;
}

if ($function =="addFarmers"){
    $farmerName = $_POST['farmerName'];
    $farmerPhoneNumber = formatPhoneNumber($_POST['farmerPhoneNumber']);
    $location = $_POST['location'];
    $locationAddress = $_POST['locationAddress'];
    $status = 'active';

    if(accountDetails($farmerPhoneNumber,"exits") < 1){
        $sql ="INSERT INTO `farmers`(`farmerName`, `farmerPhoneNumber`, `location`, `locationAddress`, `status`) 
                         VALUES ('$farmerName','$farmerPhoneNumber','$location','$locationAddress','$status') ";
        $result = DB::instance()->executeSQL($sql);
        if($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Farmer added Successfully";
            $response->success = true;
            echo json_encode($response);
            exit();

        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message= "Fail to add farmer";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Farmer exits";
        $response->success = false;
        echo json_encode($response);
        exit();
    }


}
if ($function =="getFarmers"){


    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `farmers` WHERE `farmerName` LIKE '%$keWord%' OR `farmerPhoneNumber` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `farmers` ";
    }

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateFarmers"){
    $farmerName = $_POST['farmerName'];
    $farmerPhoneNumber = formatPhoneNumber($_POST['farmerPhoneNumber']);
    $location = $_POST['location'];
    $locationAddress = $_POST['locationAddress'];
    $status = 'active';

    $sql ="UPDATE `farmers` SET `farmerName`='$farmerName',`location`='$location',`locationAddress`='$locationAddress',`status`='$status' WHERE `farmerPhoneNumber` ='$farmerPhoneNumber'";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Updated";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="deleteFarmers"){

    $farmerPhoneNumber = formatPhoneNumber($_POST['farmerPhoneNumber']);

    $sql ="DELETE FROM `farmers` WHERE `farmerPhoneNumber` = '$farmerPhoneNumber'";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Deleted";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addCategory") {
    $categoryName = $_POST['categoryName'];
    $categoryUnits = $_POST['categoryUnits'];
    $categoryAmount = $_POST['categoryAmount'];

    $sql = "INSERT INTO `categories`( `categoryName`, `categoryUnits`, `categoryAmount`) 
                  VALUES ('$categoryName','$categoryUnits','$categoryAmount')";
    $result = DB::instance()->executeSQL($sql);

    if ($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "Added successfully";
        $response->success = true;
        echo json_encode($response);
        exit();
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "Failed to add";
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}
if ($function =="getCategory"){
    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `categories` WHERE `categoryName` LIKE '%$keWord%' OR `categoryUnits` LIKE '%$keWord%' OR `categoryAmount` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `categories` ";
    }

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="deleteCategory"){
    $id = $_POST['id'];
    $sql ="DELETE FROM `categories` WHERE `id` = '$id'";
    $result = DB::instance()->executeSQL($sql);
    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Deleted";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateCategory"){
    $id = $_POST['id'];
    $categoryName = $_POST['categoryName'];
    $categoryUnits = $_POST['categoryUnits'];
    $categoryAmount = $_POST['categoryAmount'];

    $sql ="UPDATE `categories` SET `categoryName`='$categoryName',`categoryUnits`='$categoryUnits',`categoryAmount`='$categoryAmount' WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);
    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Updated";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addClient") {
    $clientName = $_POST['clientName'];
    $clientPhoneNumber = formatPhoneNumber($_POST['clientPhoneNumber']);
    $location = $_POST['location'];
    $locationAddress = $_POST['locationAddress'];
    $password = $_POST['password'];
    $status = 'active';

    if (accountDetails($clientPhoneNumber,'exits') <1){

        $sql ="INSERT INTO `clients`( `clientName`, `clientPhoneNumber`, `location`, `locationAddress`, `password`, `status`) 
                         VALUES ('$clientName','$clientPhoneNumber','$location','$locationAddress','$password','$status')";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Added successfully";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed to add";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "Client Exits";
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}
if ($function =="updateClient") {

    $clientName = $_POST['clientName'];
    $clientPhoneNumber = formatPhoneNumber($_POST['clientPhoneNumber']);
    $location = $_POST['location'];
    $locationAddress = $_POST['locationAddress'];
    $password = $_POST['password'];
    $status = 'active';

        $sql ="UPDATE `clients` SET `clientName`='$clientName', `location`='$location',`locationAddress`='$locationAddress',`password`='$password' WHERE `clientPhoneNumber` ='$clientPhoneNumber';";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Updated";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed";
            $response->success = false;
            echo json_encode($response);
            exit();
        }

}
if ($function =="deleteClient") {
    $clientPhoneNumber = formatPhoneNumber($_POST['clientPhoneNumber']);
    $sql ="DELETE FROM `clients` WHERE `clientPhoneNumber` = '$clientPhoneNumber'";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Deleted";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed";
            $response->success = false;
            echo json_encode($response);
            exit();
        }

}
if ($function =="getClients"){

    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `clients` WHERE `clientName` LIKE '%$keWord%' OR `clientPhoneNumber` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `clients` ";
    }

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addProductCategory") {
    $productCategoryName = $_POST['productCategoryName'];
    $productCategoryId = $_POST['productCategoryId'];


    if(accountDetails($productCategoryId,'exits') < 1){


        $sql ="INSERT INTO `productscategories`(`productCategoryId`, `productCategoryName`)
                                    VALUES ('$productCategoryId','$productCategoryName')";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Added successfully";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed to add";
            $response->success = false;
            echo json_encode($response);
            exit();
        }
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = "Exits";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateProductCategory") {
    $productCategoryName = $_POST['productCategoryName'];
    $productCategoryId = $_POST['productCategoryId'];
    $id = $_POST['id'];

        $sql ="UPDATE `productscategories` SET `productCategoryId`='$productCategoryId',`productCategoryName`='$productCategoryName' WHERE `id` ='$id'";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "updated";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed";
            $response->success = false;
            echo json_encode($response);
            exit();
        }


}
if ($function =="deleteProductCategory") {
        $id = $_POST['id'];
        $sql ="DELETE FROM `productscategories` WHERE `id` ='$id'";
        $result = DB::instance()->executeSQL($sql);

        if ($result){
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Deleted";
            $response->success = true;
            echo json_encode($response);
            exit();
        }else{
            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->message = "Failed";
            $response->success = false;
            echo json_encode($response);
            exit();
        }


}
if ($function =="getProductCategory"){

    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `productscategories` WHERE `productCategoryId` LIKE '%$keWord%' OR `productCategoryName` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `productscategories` ";
    }


    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addProducts"){
    $productCategoryId = $_POST['productCategoryId'];
    $productCategoryName = $_POST['productCategoryName'];
    $productName = $_POST['productName'];
    $productPrice = $_POST['productPrice'];
    $productQuantity =$_POST['productQuantity'];

    $sql ="INSERT INTO `products`( `productCategoryId`,`productCategoryName`, `productName`, `productPrice`,`productQuantity`) VALUES ('$productCategoryId','$productCategoryName','$productName','$productPrice','$productQuantity')";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Created";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateProducts"){
    $productCartegoryId = $_POST['productCartegoryId'];
    $productName = $_POST['productName'];
    $productPrice = $_POST['productPrice'];
    $productQuantity = $_POST['productQuantity'];
    $id = $_POST['id'];

    $sql ="UPDATE `products` SET `productCartegoryId`='$productCartegoryId',`productName`='$productName',`productPrice`='$productPrice',`productQuantity`='$productQuantity' WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Updated";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="deleteProducts"){
    $id = $_POST['id'];
    $sql ="DELETE FROM `products` WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Deleted";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getProducts"){

    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `products` WHERE `productCartegoryId` LIKE '%$keWord%' OR `productName` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `products` ";
    }

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addUser"){
    $userName = $_POST['userName'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql ="INSERT INTO `users`(`userName`, `email`, `password`) VALUES ('$userName','$email','$password')";
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
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateUser"){
    $userName = $_POST['userName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $id = $_POST['id'];

    $sql ="UPDATE `users` SET `userName`='$userName',`email`='$email',`password`='$password' WHERE `id` = '$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "updated";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="deleteUser"){

    $id = $_POST['id'];
    $sql ="DELETE FROM `users` WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "deleted";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getUser"){
    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `users` WHERE `userName` LIKE '%$keWord%' OR `email` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `users` ";
    }

    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}


if ($function =="addAgent"){
    $agentNumber = $_POST['agentNumber'];
    $agentName = $_POST['agentName'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $agentType = $_POST['agentType'];

    $sql ="INSERT INTO `agents`(`agentType`,`agentNumber`, `agentName`, `password`, `email`) VALUES ('$agentType','$agentNumber','$agentName','$password','$email')";
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
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="updateAgent"){
    $agentNumber = $_POST['agentNumber'];
    $agentName = $_POST['agentName'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $id = $_POST['id'];

    $sql ="UPDATE `agents` SET `agentNumber`='$agentNumber',`agentName`='$agentName',`password`='$password',`email`='$email' WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "updated";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="deleteAgent"){

    $id = $_POST['id'];
    $sql ="DELETE FROM `agents` WHERE `id` ='$id'";
    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "deleted";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getAgent"){
    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        $sql ="SELECT * FROM `agents` WHERE `agentType` ='$keWord' AND `agentNumber` LIKE '%$keWord%' OR `agentName` LIKE '%$keWord%' OR `email` LIKE '%$keWord%'";
    }else{
        $sql ="SELECT * FROM `agents` ";
    }

    echo $sql;
    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}


if ($function =="addCollection"){
    $invoiceNumber = rand ( 10000 , 99999 );
    $agentNumber = $_POST['agentNumber'];
    $farmerName = $_POST['farmerName'];
    $famerphoneNumber = $_POST['famerphoneNumber'];
    $cartegoryName = $_POST['cartegoryName'];
    $cartegoryUnits = $_POST['cartegoryUnits'];
    $unit = $_POST['unit'];
    $totalCollectionAmount = $_POST['totalCollectionAmount'];
    $amountPaid = '0';
    $balance = $totalCollectionAmount;
    $status = 'unpaid';

    $sql ="INSERT INTO `collections`(`invoiceNumber`, `agentNumber`, `farmerName`, `famerphoneNumber`, `cartegoryName`, `cartegoryUnits`, `unit`, `totalCollectionAmount`, `amountPaid`, `balance`, `status`)
                               VALUES ('$invoiceNumber','$agentNumber','$farmerName','$famerphoneNumber','$cartegoryName','$cartegoryUnits','$unit','$totalCollectionAmount','$amountPaid','$balance','$status')";

    $result = DB::instance()->executeSQL($sql);
    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "sent";
        $response->success = true;
        echo json_encode($response);
        exit();
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getCollection"){
    $sql = "";
    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        if(!empty($_POST['limit'])) {
            $limit = $_POST['limit'];
            $sql = "SELECT * FROM `collections` WHERE `agentNumber` LIKE '%$keWord%' OR `farmerName` LIKE '%$keWord%' OR `famerphoneNumber` LIKE '%$keWord%' 
         OR `cartegoryName` LIKE '%$keWord%' OR `cartegoryUnits` LIKE '%$keWord%' ORDER BY `id` DESC LIMIT  $limit";
        }else{
            $sql = "SELECT * FROM `collections` WHERE `agentNumber` LIKE '%$keWord%' OR `farmerName` LIKE '%$keWord%' OR `famerphoneNumber` LIKE '%$keWord%' 
         OR `cartegoryName` LIKE '%$keWord%' OR `cartegoryUnits` LIKE '%$keWord%' ORDER BY `id` DESC";
        }
    }else{
        $sql ="SELECT * FROM `collections` ";
    }


    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="addSalesOrder"){
    $invoiceNumber = rand ( 10000 , 99999 );
    $agentNumber = $_POST['agentNumber'];
    $clientName = $_POST['clientName'];
    $clientPhoneNumber = $_POST['clientPhoneNumber'];
    $productName = $_POST['productName'];
    $productQuantity = $_POST['productQuantity'];
    $totalQuantityAmount = $_POST['totalQuantityAmount'];
    $totalQuantityAmountPaid = '0';
    $balance = $totalQuantityAmount;
    $status = 'unpaid';
    $payNow = $_POST['payNow'];

    $message = "Invoice No.$invoiceNumber amount Kes.$totalQuantityAmount . Paybill 256666 for $productName $productQuantity units.";

    if ($payNow==="true"){
        //pay now
        pushPayments($totalQuantityAmount,"254719401837",$invoiceNumber);
        sendSMS("254719401837",$message);
    }else{
        //send sms to customer
        sendSMS("254719401837",$message);
    }

    $sql ="INSERT INTO `salesorders`(`invoiceNumber`, `agentNumber`,`clientName`, `clientPhoneNumber`, `productName`, `productQuantity`, `totalQuantityAmount`, `totalQuantityAmountPaid`, `balance`, `status`) VALUES
                                    ('$invoiceNumber','$agentNumber','$clientName','$clientPhoneNumber','$productName','$productQuantity','$totalQuantityAmount','$totalQuantityAmountPaid','$balance','$status')";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "Order sent";
        $response->success = true;
        echo json_encode($response);
        exit();
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "failed";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getSalesOrder"){
    $sql = "";
    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        if(!empty($_POST['limit'])) {
            $limit = $_POST['limit'];
            $sql = "SELECT * FROM `salesorders` WHERE `agentNumber` LIKE '%$keWord%' OR `clientName` LIKE '%$keWord%' OR `clientPhoneNumber` LIKE '%$keWord%' 
         OR `productCartogryName` LIKE '%$keWord%' OR `invoiceNumber` LIKE '%$keWord%'  OR `status` LIKE '%$keWord%' ORDER BY `id` DESC LIMIT  '$limit'";
        }else{
            $sql = "SELECT * FROM `salesorders` WHERE `agentNumber` LIKE '%$keWord%' OR `clientName` LIKE '%$keWord%' OR `clientPhoneNumber` LIKE '%$keWord%' 
         OR `productCartogryName` LIKE '%$keWord%' OR `invoiceNumber` LIKE '%$keWord%'  OR `status` LIKE '%$keWord%' ORDER BY `id` DESC";
        }

    }else{
        $sql ="SELECT * FROM `salesorders` ORDER BY `id` DESC";
    }


    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if ($function =="transaction"){
    $transactionId = $_POST['transactionId'];
    $invoiceNumber = $_POST['invoiceNumber'];
    $phoneNumber = $_POST['phoneNumber'];
    $amount = $_POST['amount'];
    $name = $_POST['name'];
    $payBillNumber = $_POST['payBillNumber'];
    $status = $_POST['status'];

    $sql ="INSERT INTO `transactions`(`transactionId`, `invoiceNumber`, `phoneNumber`, `amount`, `name`, `payBillNumber`, `status`) 
                       VALUES ('$transactionId','$invoiceNumber','$phoneNumber','$amount','$name','$payBillNumber','$status')";

    $result = DB::instance()->executeSQL($sql);

    if($result){
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "success";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}
if ($function =="getTransactions"){

    if(!empty($_POST["keyword"])) {
        $keWord = $_POST["keyword"];
        if(!empty($_POST['limit'])) {
            $limit = $_POST['limit'];
            $sql = "SELECT * FROM `transactions` WHERE `transactionId` LIKE '%$keWord%' OR `invoiceNumber` LIKE '%$keWord%' OR `phoneNumber` LIKE '%$keWord%' 
         OR `name` LIKE '%$keWord%' OR `payBillNumber` LIKE '%$keWord%'  OR `status` LIKE '%$keWord%' ORDER BY `id` DESC LIMIT  $limit";
        }else{
            $sql = "SELECT * FROM `transactions` WHERE `transactionId` LIKE '%$keWord%' OR `invoiceNumber` LIKE '%$keWord%' OR `phoneNumber` LIKE '%$keWord%' 
         OR `name` LIKE '%$keWord%' OR `payBillNumber` LIKE '%$keWord%'  OR `status` LIKE '%$keWord%' ORDER BY `id` DESC";
        }

    }else{
        $sql ="SELECT * FROM `transactions` ORDER BY `id` DESC ";
    }


    $result = DB::instance()->executeSQL($sql);

    if($result->num_rows > 0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }

}

if($function =="totalTransactions"){
    $sql ="";
    if(!empty($_POST['payBillNumber'])){
        $status = $_POST['status'];
        $payBillNumber = $_POST['payBillNumber'];

        $sql = "SELECT SUM(amount)
                      FROM `transactions`
                   WHERE paybillNumber = '$payBillNumber' AND status = '$status'";
    }

    $results = DB::instance()->executeSQL($sql);

    if($results ->num_rows >0){
       /* $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Total sum for transactions with paybill number '.$payBillNumber;
        $response->data= $results->fetch_array()[0];
        $response->success = true;
        echo json_encode($response);*/

       echo $results->fetch_array()[0];
    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'No Transactions found with pay bill number '.$payBillNumber;
        $response->success = false;
        echo json_encode($response);
    }
}

if($function =="login"){
    $email = $_POST['email'];
    $password = $_POST['password'];

    login($email, $password);
}

function login ($email, $password) {

    if(accountDetails($email,'exits') >0){
        $sql ="SELECT * FROM `agents` WHERE `email` ='$email' AND `password` ='$password'";
        $result = DB::instance()->executeSQL($sql);
        if($result ->num_rows > 0){

            $response = new Response();
            $response->status = Response::STATUS_SUCCESS;
            $response->data =$result->fetch_assoc();
            $response->message = "ok";
            $response->success = true;
            echo json_encode($response);
        }else{
            $sql ="SELECT * FROM `users` WHERE `email` ='$email' AND `password` ='$password'";
            $result = DB::instance()->executeSQL($sql);
            if($result ->num_rows > 0){


                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->data =$result->fetch_assoc();
                $response->message = "ok";
                $response->success = true;
                echo json_encode($response);
            }else{
                $response = new Response();
                $response->status = Response::STATUS_SUCCESS;
                $response->message = 'invalid credentials';
                $response->success = false;
                echo json_encode($response);

            }

        }

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Account does not exits';
        $response->success = false;
        echo json_encode($response);
        exit();
    }


}

if($function =="callback"){
    $transactionId = "";
    $invoiceNumber = $_POST['BillRefNumber'];
    $phoneNumber = $_POST['MSISDN'];
    $amount = $_POST['TransAmount'];
    $name ="";
    $payBillNumber = $_POST['BusinessShortCode'];
    $status ="1";



    $sql ="INSERT INTO `transactions`(`transactionId`, `invoiceNumber`, `phoneNumber`, `amount`, `name`, `payBillNumber`, `status`) 
                       VALUES ('$transactionId','$invoiceNumber','$phoneNumber','$amount','$name','$payBillNumber','1')";

    $result = DB::instance()->executeSQL($sql);

    if($result){
       $amount=  accountBalance($invoiceNumber);

        $sql = "UPDATE `salesorders` SET `totalQuantityAmountPaid`='$amount' WHERE `invoiceNumber` ='$invoiceNumber'";
        DB::instance()->executeSQL($sql);

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "paid";
        $response->success = true;
        echo json_encode($response);
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message= "No Data found";
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}





/*** Kenya Nut End*/




function accountBalance($accountNumber) {

    $sqlTotal = "SELECT SUM(amount)
                       FROM `transactions`
                       WHERE invoiceNumber = '$accountNumber'";

    $results = DB::instance()->executeSQL($sqlTotal);

        if ($results)
            return $results->fetch_array()[0];
    exit();
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
        exit();

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = '';
        $response->data = [];
        $response->success = false;
        echo json_encode($response);
        exit();

    }
}

function storeUpdateRecords($company){
    //gets records from uploads and merges them to customers table
    $sqlUploads= "SELECT * FROM `uploads` WHERE `company`='$company'";
    $resultUploads = DB::instance()->executeSQL($sqlUploads);

    if ($resultUploads){
        while( $rowUploads = $resultUploads->fetch_assoc()){

            $phoneNumber = $rowUploads['phoneNumber'];
            $memberId =$rowUploads['memberId'];
            $name =$rowUploads['name'];

            $sqlCust= "SELECT * FROM `customers` WHERE `memberId`='$memberId' AND `company`='$company'";
            $resultsCust = DB::instance()->executeSQL($sqlCust);

            if($resultsCust->num_rows<1){

                $sql = "INSERT INTO `customers`( `memberId`, `name`, `phoneNumber`, `company`) 
                            VALUES ('$memberId','$name','$phoneNumber','$company')";
                DB::instance()->executeSQL($sql);
            }
        }


    }



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
        exit();
    } else {
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Failed';
        $response->data = "Balance: KES " . accountBalance($company);
        $response->success = false;
        echo json_encode($response);
        exit();
    }
}

if($function == "accountTop"){
    $email = $_POST['email'];
    $amount = $_POST['amount'];

    accountTopup($email, $amount,confirmatioCode(),'');

}

function sendSMS($phoneNumber,$message){
    $username   = "Nouveta";
    $apikey     = "df338bb1b4ce3c568e0bbf619d1ffde365f820e1d9a89eb5d77ab7d298997e0d";

    $gateway    = new AfricasTalkingGateway($username, $apikey);
    try
    {
        // Thats it, hit send and we'll take care of the rest.
        $results = $gateway->sendMessage($phoneNumber, $message);

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

function airTime($memberId,$name,$phoneNumber,$amount,$email){

    $username = "Nouveta";
    $apiKey   = "111fab0e6ea5af08eb6601245531a98584ef2ede47c36621d5e6ab08b81fb6e5";
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


function pushPayments($amount, $phoneNumber, $code){

    $curl = curl_init();
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


?>