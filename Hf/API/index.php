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
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set("Africa/Nairobi");
header("Access-Control-Allow-Origin: *");

$function = $_REQUEST['function'];

if($function=="reconCount"){
    $sql ="SELECT SUM(total_records) FROM `report_count`";

    $sumRecords = DB::instance()->executeSQL($sql)->fetch_array()[0];

    $sql_report_count ="SELECT * FROM `report_count` ";

    $result = DB::instance()->executeSQL($sql_report_count);
    if($result){
        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data=  array("reportsCount"=>$new_array,"TotalRecordsSum"=>$sumRecords);
        $response->success = true;
        echo json_encode($response);

    }


}


if($function =="uploadExcelMpesaSafaricom"){
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
                $ReceiptNo =removeSpaces($worksheet->getCellByColumnAndRow(0, $row)->getValue());
                $CompletionTime =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $InitiationTime = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $Details =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $TransactionStatus =  $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                $PaidIn =  toInt($worksheet->getCellByColumnAndRow(5, $row)->getValue());
                $Withdrawn =  $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                $Balance =  toInt($worksheet->getCellByColumnAndRow(7, $row)->getValue());
                $BalanceConfirmed =  $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                $ReasonType =  $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                $OtherPartyInfo =  $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                $LinkedTransactionID =  $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                $ACNo =  $worksheet->getCellByColumnAndRow(12, $row)->getValue();



                $sql = "INSERT INTO `mpesa_transaction`(`ReceiptNo`, `CompletionTime`, `InitiationTime`, `Details`, `TransactionStatus`, `PaidIn`, `Withdrawn`, `Balance`, `BalanceConfirmed`, `ReasonType`, `OtherPartyInfo`, `LinkedTransactionID`, `ACNo`) 
                                                VALUES ('$ReceiptNo','$CompletionTime','$InitiationTime','$Details','$TransactionStatus','$PaidIn','$Withdrawn','$Balance','$BalanceConfirmed','$ReasonType','$OtherPartyInfo','$LinkedTransactionID','$ACNo')";
                $result = DB::instance()->executeSQL($sql);
            }
        }



        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions uploaded" ;
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
if($function =="uploadExcelKocela"){
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
                $InternalRef =removeSpaces($worksheet->getCellByColumnAndRow(0, $row)->getValue());
                $TransactionRef =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $PhoneNumber = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $Service =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $Amount =  $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                $UserAccount =  toInt($worksheet->getCellByColumnAndRow(5, $row)->getValue());
                $Destination =  $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                $status =  toInt($worksheet->getCellByColumnAndRow(7, $row)->getValue());
                $StatusMessage =  $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                $DateCreated =  $worksheet->getCellByColumnAndRow(9, $row)->getValue();


                $sql = "INSERT INTO `kocela_transactions`(`InternalRef`, `TransactionRef`, `PhoneNumber`, `Service`, `Amount`, `UserAccount`, `Destination`, `status`, `StatusMessage`, `DateCreated`)
                          VALUES ('$InternalRef','$TransactionRef','$PhoneNumber','$Service','$Amount','$UserAccount','$Destination','$status','$StatusMessage','$DateCreated')";
                $result = DB::instance()->executeSQL($sql);
            }
        }



        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions uploaded" ;
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
if($function =="uploadExcelMpesaCoreBanking"){
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
                $LineNo =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                $TrxDate =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $TrxSN = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $TUNInternalSN =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $Timestamp =  $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                $Code =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                $TransactionName =  $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                $Comments = removeSpaces($worksheet->getCellByColumnAndRow(7, $row)->getValue());
                $JustificationCode =  $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                $JustificationName=  $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                $AccountNumber =  $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                $BeneficiariesName =  $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                $Currency =  $worksheet->getCellByColumnAndRow(12, $row)->getValue();
                $ChequeNumber =  $worksheet->getCellByColumnAndRow(13, $row)->getValue();
                $RV=  $worksheet->getCellByColumnAndRow(14, $row)->getValue();
                $Debit =  $worksheet->getCellByColumnAndRow(15, $row)->getValue();
                $Credit =  toInt($worksheet->getCellByColumnAndRow(16, $row)->getValue());
                $ChargesAmt=  $worksheet->getCellByColumnAndRow(17, $row)->getValue();
                $Authorizer1 =  $worksheet->getCellByColumnAndRow(18, $row)->getValue();
                $Authorizer2 =  $worksheet->getCellByColumnAndRow(19, $row)->getValue();


                $Timestamp= gmdate("m-d-Y h:m:s", $Timestamp);



                if($TransactionName=="JOURNAL CREDIT"){

                    if(!empty($Comments)){

                        if($Credit>0){
                            $sql = "INSERT INTO `core_banking_mpesa`(`LineNo`, `TrxDate`, `TrxSN`, `TUNInternalSN`, `Timestamp`, `Code`, `TransactionName`, `Comments`, `JustificationCode`,`JustificationName`, `AccountNumber`, `BeneficiariesName`, `Currency`, `ChequeNumber`, `RV`, `Debit`, `Credit`, `ChargesAmt`, `Authorizer1`, `Authorizer2`) 
                                                 VALUES ('$LineNo','$TrxDate','$TrxSN','$TUNInternalSN','$Timestamp','$Code','$TransactionName','$Comments','$JustificationCode','$JustificationName','$AccountNumber','$BeneficiariesName','$Currency','$ChequeNumber','$RV','$Debit','$Credit','$ChargesAmt','$Authorizer1','$Authorizer2')";
                            $result = DB::instance()->executeSQL($sql);
                        }
                    }

                }

            }
        }



        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions uploaded" ;
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

if($function =="uploadExcelAtm"){
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
                $cardNumber =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                $amount = toInt($worksheet->getCellByColumnAndRow(1, $row)->getValue());
                $RRN =  ltrim($worksheet->getCellByColumnAndRow(2, $row)->getValue(), '0');
                $terminal =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $account =   ltrim($worksheet->getCellByColumnAndRow(4, $row)->getValue(), '0');;
                $timestamp =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();

                $sql = "INSERT INTO `atm_transaction`(`cardNumber`, `amount`, `RRN`, `terminal`, `account`, `timestamp`)
                                               VALUES ('$cardNumber','$amount','$RRN','$terminal','$account','$timestamp')";
                $result = DB::instance()->executeSQL($sql);
            }
        }



        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions Uploaded" ;
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
if($function =="uploadExcelAtmCoreBanking"){
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
                $LineNo =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                $TrxDate =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                $TrxSN = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                $TUNInternalSN =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();
                $Timestamp =  $worksheet->getCellByColumnAndRow(4, $row)->getValue();
                $Code =  $worksheet->getCellByColumnAndRow(5, $row)->getValue();
                $TransactionName =  $worksheet->getCellByColumnAndRow(6, $row)->getValue();
                $Comments =  $worksheet->getCellByColumnAndRow(7, $row)->getValue();
                $JustificationCode =  $worksheet->getCellByColumnAndRow(8, $row)->getValue();
                $JustificationName=  $worksheet->getCellByColumnAndRow(9, $row)->getValue();
                $AccountNumber =  $worksheet->getCellByColumnAndRow(10, $row)->getValue();
                $BeneficiariesName =  $worksheet->getCellByColumnAndRow(11, $row)->getValue();
                $Currency =  $worksheet->getCellByColumnAndRow(12, $row)->getValue();
                $ChequeNumber =  $worksheet->getCellByColumnAndRow(13, $row)->getValue();
                $RV=  $worksheet->getCellByColumnAndRow(14, $row)->getValue();
                $Debit =  toInt($worksheet->getCellByColumnAndRow(15, $row)->getValue());
                $Credit =  toInt($worksheet->getCellByColumnAndRow(16, $row)->getValue());
                $ChargesAmt=  $worksheet->getCellByColumnAndRow(17, $row)->getValue();
                $Authorizer1 =  $worksheet->getCellByColumnAndRow(18, $row)->getValue();
                $Authorizer2 =  $worksheet->getCellByColumnAndRow(19, $row)->getValue();

                $CommentsArray = explode(" | ", $Comments);
                $RRN= ltrim($CommentsArray[1], '0'); ;

                $Comments = $CommentsArray[0] ." | ".$RRN;




                $sql = "INSERT INTO `core_banking_atm`(`LineNo`, `TrxDate`, `TrxSN`, `TUNInternalSN`, `Timestamp`, `Code`, `TransactionName`, `Comments`, `JustificationCode`,`JustificationName`, `AccountNumber`, `BeneficiariesName`, `Currency`, `ChequeNumber`, `RV`, `Debit`, `Credit`, `ChargesAmt`, `Authorizer1`, `Authorizer2`) 
                                                 VALUES ('$LineNo','$TrxDate','$TrxSN','$TUNInternalSN','$Timestamp','$Code','$TransactionName','$Comments','$JustificationCode','$JustificationName','$AccountNumber','$BeneficiariesName','$Currency','$ChequeNumber','$RV','$Debit','$Credit','$ChargesAmt','$Authorizer1','$Authorizer2')";
                $result = DB::instance()->executeSQL($sql);
            }
        }



        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions uploaded" ;
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

if ($function =="get_core_banking_mpesa"){
    $keyword = $_POST['keyword'];

    if(empty($keyword)){
        $sql ="SELECT * FROM `core_banking_mpesa` ";
    }else{

        $sql ="SELECT * FROM `core_banking_mpesa` WHERE `LineNo` LIKE '%$keyword%' OR `TrxDate` LIKE '%%' OR `TrxSN` LIKE '%%' OR `TUNInternalSN` LIKE '%%' OR `Timestamp` LIKE '%$keyword%' OR 
`Code` LIKE '%$keyword%' OR`TransactionName` LIKE '%$keyword%' OR`Comments`LIKE '%$keyword%' OR`JustificationCode`LIKE '%$keyword%' OR`AccountNumber`LIKE '%$keyword%' OR`BeneficiariesName`LIKE '%$keyword%' OR`Currency`LIKE '%$keyword%' OR`ChequeNumber`LIKE '%$keyword%' OR `RV` LIKE '%$keyword%' OR `Debit` LIKE '%$keyword%' OR`Credit` LIKE '%$keyword%' OR `ChargesAmt` LIKE '%$keyword%' OR `Authorizer1` LIKE '%$keyword%' OR`Authorizer2` LIKE '%$keyword%' OR`des` = '$keyword' OR `reconcile` LIKE '%$keyword%'  ";
    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->data = $new_array ;
        $response->message =" $result->num_rows Transactions" ;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if ($function =="get_mpesa_transaction"){
    $keyword = $_POST['keyword'];

    if(empty($keyword)){
        $sql ="SELECT * FROM `mpesa_transaction` ";
    }else{

        $sql ="SELECT * FROM `mpesa_transaction` WHERE `ReceiptNo` LIKE '%$keyword%' OR `CompletionTime` LIKE '%$keyword%' 
                OR `InitiationTime` LIKE '%$keyword%' OR `Details` LIKE '%$keyword%' OR`TransactionStatus` LIKE '%$keyword%' OR `PaidIn` LIKE '%$keyword%' 
                OR `Withdrawn` LIKE '%$keyword%' OR `Balance` LIKE '%$keyword%' OR `BalanceConfirmed` LIKE '%$keyword%' OR `ReasonType` LIKE '%$keyword%' OR `OtherPartyInfo` 
                LIKE '%$keyword%' OR `LinkedTransactionID` LIKE '%$keyword%' OR `ACNo` LIKE '%$keyword%' OR `date` LIKE '%$keyword%' OR `reconcile` LIKE '%$keyword%'";

    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if ($function =="get_kocela_transaction"){
    $keyword = $_POST['keyword'];

    if(empty($keyword)){
        $sql ="SELECT * FROM `kocela_transactions` ";
    }else{

        $sql ="SELECT * FROM `kocela_transactions` WHERE `ReceiptNo` LIKE '%$keyword%' OR `CompletionTime` LIKE '%$keyword%' 
                OR `InitiationTime` LIKE '%$keyword%' OR `Details` LIKE '%$keyword%' OR`TransactionStatus` LIKE '%$keyword%' OR `PaidIn` LIKE '%$keyword%' 
                OR `Withdrawn` LIKE '%$keyword%' OR `Balance` LIKE '%$keyword%' OR `BalanceConfirmed` LIKE '%$keyword%' OR `ReasonType` LIKE '%$keyword%' OR `OtherPartyInfo` 
                LIKE '%$keyword%' OR `LinkedTransactionID` LIKE '%$keyword%' OR `ACNo` LIKE '%$keyword%' OR `date` LIKE '%$keyword%' OR `reconcile` LIKE '%$keyword%'";

    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}

if ($function =="get_core_banking_atm"){
    $keyword = $_POST['keyword'];

    if(empty($keyword)){
        $sql ="SELECT * FROM `core_banking_atm` ";
    }else{

        $sql ="SELECT * FROM `core_banking_atm` WHERE `LineNo` LIKE '%$keyword%' OR `TrxDate` LIKE '%$keyword%' OR `TrxSN` LIKE '%$keyword%' OR `TUNInternalSN` LIKE '%$keyword%' OR `Timestamp` LIKE '%$keyword%' OR `Code` LIKE '%$keyword%' OR `TransactionName` LIKE '%$keyword%' OR `Comments` LIKE '%$keyword%' OR `JustificationCode` LIKE '%$keyword%' OR `JustificationName` LIKE '%$keyword%' OR `AccountNumber` LIKE '%$keyword%' OR `BeneficiariesName` LIKE '%$keyword%' OR `Currency` LIKE '%$keyword%' OR `ChequeNumber` LIKE '%$keyword%' OR `RV` LIKE '%$keyword%' OR `Debit` LIKE '%$keyword%' OR `Credit` LIKE '%$keyword%' OR `ChargesAmt` LIKE '%$keyword%' OR `Authorizer1` LIKE '%$keyword%' OR `Authorizer2` LIKE '%$keyword%' OR `des` = '$keyword' OR `reconcile` LIKE '%$keyword%'";
    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}
if ($function =="get_atm_transaction"){
    $keyword = $_POST['keyword'];

    if(empty($keyword)){
        $sql ="SELECT * FROM `atm_transaction` ";
    }else{

        $sql ="SELECT * FROM `atm_transaction` WHERE `cardNumber` LIKE '%$keyword%' OR `amount` LIKE '%$keyword%' OR `RRN` LIKE '%$keyword%' OR `terminal` LIKE '%$keyword%' OR `account` LIKE '%$keyword%' OR `timestamp` LIKE '%$keyword%' OR `date` LIKE '%$keyword%' OR `reconcile`='%keyword%'";
    }

    $result = DB::instance()->executeSQL($sql);

    if ($result->num_rows >0){

        while( $row = $result->fetch_assoc()){
            $new_array[] = $row; // Inside while loop
        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data= $new_array;
        $response->success = true;
        echo json_encode($response);


    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message =" $result->num_rows Transactions" ;
        $response->data = [];
        $response->success = false;
        echo json_encode($response);


    }

}

if($function=="dashboardDataCoreBanking"){

    //INTERNAL REPORTS
    //core banking Mpesa
    $sqlRecMpesaCB ="SELECT SUM(Credit)
                       FROM `core_banking_mpesa` WHERE `reconcile` ='1'
                    ";
    $sumReconcileMpesaCB = DB::instance()->executeSQL($sqlRecMpesaCB)->fetch_array()[0];

    $sqlNotRecMpesaCB ="SELECT SUM(Credit)
                       FROM `core_banking_mpesa` WHERE `reconcile` ='0'
                    ";
    $sumNotReconcileMpesaCB = DB::instance()->executeSQL($sqlNotRecMpesaCB)->fetch_array()[0];

    $sqlFundInAccountMpesaCB ="SELECT SUM(Credit)
                       FROM `core_banking_mpesa`
                    ";
    $fundInAccountMpesaCB = DB::instance()->executeSQL($sqlFundInAccountMpesaCB)->fetch_array()[0];


    //core banking atms
    $sqlRecAtmCB ="SELECT SUM(Debit)
                       FROM `core_banking_atm` WHERE `reconcile` ='1'
                    ";
    $sumReconcileAtmCB = DB::instance()->executeSQL($sqlRecAtmCB)->fetch_array()[0];

    $sqlNotRecAtmCB ="SELECT SUM(Debit)
                       FROM `core_banking_atm` WHERE `reconcile` ='0'
                    ";
    $sumNotReconcileAtmCB = DB::instance()->executeSQL($sqlNotRecAtmCB)->fetch_array()[0];

    $sqlFundInAccountAtmCB ="SELECT SUM(Debit)
                       FROM `core_banking_atm`
                    ";
    $fundInAccountATmCB = DB::instance()->executeSQL($sqlFundInAccountAtmCB)->fetch_array()[0];

    if($fundInAccountMpesaCB==null)
        $fundInAccountMpesaCB=0;

    if($sumReconcileMpesaCB==null)
        $sumReconcileMpesaCB="0";

    if($sumNotReconcileMpesaCB==null)
        $sumNotReconcileMpesaCB="0";

    if($fundInAccountATmCB==null)
        $fundInAccountATmCB="0";

    if($sumReconcileAtmCB==null)
        $sumReconcileAtmCB="0";

    if($sumNotReconcileAtmCB==null)
        $sumNotReconcileAtmCB="0";


    //EXTERNAL REPORTS
    //MPESA reports
    $sqlRecMpesaExternal ="SELECT SUM(PaidIn)
                       FROM `mpesa_transaction` WHERE `reconcile` ='1'
                    ";
    $sumReconcileMpesaExternal = DB::instance()->executeSQL($sqlRecMpesaExternal)->fetch_array()[0];

    $sqlNotRecMpesaExternal ="SELECT SUM(PaidIn)
                       FROM `mpesa_transaction` WHERE `reconcile` ='0'
                    ";
    $sumNotReconcileMpesaExternal = DB::instance()->executeSQL($sqlNotRecMpesaExternal)->fetch_array()[0];

    $sqlFundInAccountMpesaExternal ="SELECT SUM(PaidIn)
                       FROM `mpesa_transaction`
                    ";
    $fundInAccountMpesaExternal = DB::instance()->executeSQL($sqlFundInAccountMpesaExternal)->fetch_array()[0];


    //ATM reports
    $sqlRecAtmExternal ="SELECT SUM(amount)
                       FROM `atm_transaction` WHERE `reconcile` ='1'
                    ";
    $sumReconcileAtmExternal = DB::instance()->executeSQL($sqlRecAtmExternal)->fetch_array()[0];

    $sqlNotRecAtmExternal ="SELECT SUM(amount)
                       FROM `atm_transaction` WHERE `reconcile` ='0'
                    ";
    $sumNotReconcileAtmExternal = DB::instance()->executeSQL($sqlNotRecAtmExternal)->fetch_array()[0];

    $sqlFundInAccountAtmExternal ="SELECT SUM(amount)
                       FROM `atm_transaction`
                    ";
    $fundInAccountATmExternal = DB::instance()->executeSQL($sqlFundInAccountAtmExternal)->fetch_array()[0];


    if($sumReconcileMpesaExternal==null)
        $sumReconcileMpesaExternal="0";

    if($sumNotReconcileMpesaExternal==null)
        $sumNotReconcileMpesaExternal="0";

    if($fundInAccountMpesaExternal==null)
        $fundInAccountMpesaExternal="0";

    if($sumReconcileAtmExternal==null)
        $sumReconcileAtmExternal="0";

    if($sumNotReconcileAtmExternal==null)
        $sumNotReconcileAtmExternal="0";

    if($fundInAccountATmExternal==null)
        $fundInAccountATmExternal="0";





    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->data=  array("fundInAccountMpesaCB"=> formatcurrency($fundInAccountMpesaCB),"reconciledMpesaCB"=> formatcurrency($sumReconcileMpesaCB),'notReconciledMpesaCB'=>formatcurrency($sumNotReconcileMpesaCB),
        "fundInAccountMpesaExternal"=>formatcurrency($fundInAccountMpesaExternal),"reconciledMpesaExternal"=>formatcurrency($sumReconcileMpesaExternal),'notReconciledMpesaExternal'=>formatcurrency($sumNotReconcileMpesaExternal),
        "fundInAccountATMCB"=>formatcurrency($fundInAccountATmCB),"reconciledATMCB"=>formatcurrency($sumReconcileAtmCB),'notReconciledATMCB'=>formatcurrency($sumNotReconcileAtmCB),
        "fundInAccountATMExternal"=>formatcurrency($fundInAccountATmExternal),"reconciledATMExternal"=>formatcurrency($sumReconcileAtmExternal),'notReconciledATMExternal'=>formatcurrency($sumNotReconcileAtmExternal));
    $response->success = true;
    echo json_encode($response);
    exit();

}
if($function=="clear"){
    /*$action = $_REQUEST['action'];*/
    /*if($action=="MPESA-CB")*/
        DB::instance()->executeSQL(" DELETE FROM `core_banking_mpesa`");
   /* if($action=="MPESA-EXTERNAL")*/
        DB::instance()->executeSQL(" DELETE FROM  `mpesa_transaction`");

   /* if($action=="ATM-CB")*/
        DB::instance()->executeSQL(" DELETE FROM `core_banking_atm`");
   /* if($action=="ATM-EXTERNAL")*/
        DB::instance()->executeSQL(" DELETE FROM  `atm_transaction`");

    DB::instance()->executeSQL("DELETE FROM `kocela_transactions`");

    $response = new Response();
    $response->status = Response::STATUS_SUCCESS;
    $response->message ="Transactions cleared" ;
    $response->success = true;
    echo json_encode($response);

}

function  reconcileMpesa (){

    $sql ="SELECT * FROM `core_banking_mpesa`";

    $res = DB::instance()->executeSQL($sql);

    if ($res->num_rows > 0) {

        $total_records = $res->num_rows;

        DB::instance()->executeSQL("INSERT INTO `report_count`(`total_records`) VALUES ('$total_records')");

        while ($row = $res->fetch_assoc()) {
            $id=$row['id'];
            $Credit = $row['Credit'];

            $Comments = $row['Comments'];
            $CommentsArray = explode("-", $Comments);
            $CbReceiptNo=  $CommentsArray[0];

            $sql = "SELECT * FROM `mpesa_transaction` WHERE `ReceiptNo`='$CbReceiptNo'";
            $result = DB::instance()->executeSQL($sql);
            if($result->num_rows >0){
                $paidIn = $result->fetch_assoc()['PaidIn'];

                if($Credit==$paidIn){

                    $sqlK = "SELECT * FROM `kocela_transactions` WHERE `TransactionRef` ='$CbReceiptNo'";
                    $resultK = DB::instance()->executeSQL($sqlK);

                    if($resultK->num_rows >0){
                        $Amount = $resultK->fetch_assoc()['Amount'];

                        if($Credit==$Amount){
                            DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='1' ,`des`='matched',`reconcileDesc`='Reconciled' WHERE `id` ='$id' ");
                            DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='1' ,`des`='matched' ,`reconcileDesc`='Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");
                            DB::instance()->executeSQL("UPDATE `kocela_transactions` SET `reconcile`='1' ,`des`='matched' ,`reconcileDesc`='Reconciled' WHERE `TransactionRef`='$CbReceiptNo'");

                        }else{
                            $message = "Amount MisMatched Kocela=".$Amount." CB=".$Credit."MPESA=".$paidIn;
                            DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message',`reconcileDesc`='Not Reconciled' WHERE `id` ='$id' ");
                            DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");
                            DB::instance()->executeSQL("UPDATE `kocela_transactions` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `TransactionRef`='$CbReceiptNo'");

                        }

                    }else{

                        $message = "Not Found in Kocela";
                        DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message',`reconcileDesc`='Not Reconciled' WHERE `id` ='$id' ");
                        DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");
                        DB::instance()->executeSQL("UPDATE `kocela_transactions` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `TransactionRef`='$CbReceiptNo'");

                    }


                }else{

                    $message = "MPESA=$paidIn CB=$Credit DIF = ".diff($Credit,$paidIn);
                    DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `id` LIKE '$id' ");
                    DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message'  ,`reconcileDesc`='Not Reconciled' WHERE `ReceiptNo`='$CbReceiptNo'");
                    DB::instance()->executeSQL("UPDATE `kocela_transactions` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `TransactionRef`='$CbReceiptNo'");

                }

            }else{

                $message = "unmatched";
                DB::instance()->executeSQL("UPDATE `core_banking_mpesa` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'   WHERE `id` LIKE '$id' ");
                DB::instance()->executeSQL("UPDATE `mpesa_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$CbReceiptNo'");
                DB::instance()->executeSQL("UPDATE `kocela_transactions` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled' WHERE `TransactionRef`='$CbReceiptNo'");
            }

        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Reconciliation successful';
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Nothing to Reconcile now';
        $response->success = false;
        echo json_encode($response);
    }

}
function  reconcileATM (){
    $sql ="SELECT * FROM `core_banking_atm`";

    $res = DB::instance()->executeSQL($sql);

    if ($res->num_rows > 0) {

        while ($row = $res->fetch_assoc()) {
            $id=$row['id'];
            $Debit = $row['Debit'];

            $Comments = $row['Comments'];
            $CommentsArray = explode(" | ", $Comments);
            $RRN= ltrim($CommentsArray[1], '0'); ;


            $sql_atm = "SELECT * FROM `atm_transaction` WHERE `RRN`='$RRN'";
            $result_atm = DB::instance()->executeSQL($sql_atm);
            if($result_atm->num_rows > 0){
                $amount = $result_atm->fetch_assoc()['amount'];

                if($Debit==$amount){
                    DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='1' ,`des`='matched' ,`reconcileDesc`='Reconciled'   WHERE `id` ='$id' ");
                    DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='1',`des`='matched',`reconcileDesc`='Reconciled'  WHERE `RRN`='$RRN'");


                }else{
                    $message = "ATM=$amount CB=$Debit DIF = ".diff($amount,$Debit);
                    DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'   WHERE `id` = '$id' ");
                    DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$RRN'");

                }

            }else{
                $message = "unmatched";
                DB::instance()->executeSQL("UPDATE `core_banking_atm` SET `reconcile`='0' ,`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `id` = '$id' ");
                DB::instance()->executeSQL("UPDATE `atm_transaction` SET `reconcile`='0',`des`='$message' ,`reconcileDesc`='Not Reconciled'  WHERE `ReceiptNo`='$RRN'");
            }

        }

        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Reconciliation successful';
        $response->success = true;
        echo json_encode($response);

    }else{
        $response = new Response();
        $response->status = Response::STATUS_SUCCESS;
        $response->message = 'Nothing to Reconcile now';
        $response->success = false;
        echo json_encode($response);
    }
}


if($function=="reconcileMpesa") {
    reconcileMpesa ();
}
if($function=="reconcileATM") {
    reconcileATM();
}



if($function=="match"){

    $firstPath = $_REQUEST['firstPath'];
    $secondPath = $_REQUEST['secondPath'];


    if(areEqual($firstPath,$secondPath)){
        echo "True";

    }else{
        echo "False";
    }
}

function removeSpaces($value){
    $value = str_replace(' ', '', $value);
    $value = preg_replace('/\s+/', '', $value);
    return $value;
}

function areEqual($firstPath, $secondPath, $chunkSize = 500){


    // Compare the first ${chunkSize} bytes
    // This is fast and binary files will most likely be different
    $fp1 = fopen($firstPath, 'r');
    $fp2 = fopen($secondPath, 'r');
    $chunksAreEqual = fread($fp1, $chunkSize) == fread($fp2, $chunkSize);
    fclose($fp1);
    fclose($fp2);

    if(!$chunksAreEqual){
        return false;
    }

    // Compare hashes
    // SHA1 calculates a bit faster than MD5
    $firstChecksum = sha1_file($firstPath);
    $secondChecksum = sha1_file($secondPath);
    if($firstChecksum != $secondChecksum){
        return false;
    }

    return true;
}



class PDF extends FPDF
{
// Page header
    function Header()
    {
        // Logo
        $this->Image('http://biasharaleo.co.ke/wp-content/uploads/2017/11/DK6bxmVf.png',10,-1,70);
        $this->SetFont('Arial','B',13);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(80,10,'BANK RECONCILIATION REPORT',1,0,'C');
        // Line break
        $this->Ln(50);
    }

// Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

//CORE BANKING REPORTS
if($function =="generatePDFmPESACB"){
    $reconciled = $_REQUEST['reconciled'];

    if($reconciled=='2'){
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa`";
    }else{
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa` WHERE `reconcile` ='$reconciled' ";
    }


    $result = DB::instance()->executeSQL($sql);

    $header = DB::instance()->executeSQL("SHOW COLUMNS FROM core_banking_mpesa
                                       WHERE FIELD IN('Comments','TransactionName','AccountNumber','Credit','des','reconcileDesc')");


    $display_heading = array('Comments'=> 'Comments', 'TransactionName'=> 'Transaction Name','AccountNumber'=> 'Account Number','Credit'=> 'Credit',
        'des'=> 'Des','reconcileDesc'=> 'Reconcile',);

    $pdf = new PDF();
//header
    $pdf->AddPage('Horizontal');
//foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','B',8);
    foreach($header as $heading) {
        $pdf->Cell(47,12,$display_heading[$heading['Field']],1);
    }
    foreach($result as $row) {
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(47,12,$column,1);
    }
    $pdf->Output();

}
if($function =="generatePDFaTMCB"){

    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` ";

    }else{
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` WHERE  `reconcile` =$reconciled";
    }


    $result = DB::instance()->executeSQL($sql);

    $header = DB::instance()->executeSQL("SHOW COLUMNS FROM core_banking_mpesa
                                       WHERE FIELD IN('Comments','TransactionName','AccountNumber','Credit','des','reconcileDesc')");


    $display_heading = array('Comments'=> 'Comments', 'TransactionName'=> 'TransactionName','AccountNumber'=> 'AccountNumber','Credit'=> 'Credit',
        'des'=> 'des','reconcileDesc'=> 'Reconcile');

    $pdf = new PDF();
//header
    $pdf->AddPage('Horizontal');
//foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','B',8);
    foreach($header as $heading) {
        $pdf->Cell(47,12,$display_heading[$heading['Field']],1);
    }
    foreach($result as $row) {
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(47,12,$column,1);
    }
    $pdf->Output();

}

if($function=="generateSpreadSheetMpesaCB"){

    header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
    header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Transaction Name');
    $sheet->setCellValue('B1', 'Comments');
    $sheet->setCellValue('C1', 'Account Number');
    $sheet->setCellValue('D1', 'Credit');
    $sheet->setCellValue('E1', 'des');
    $sheet->setCellValue('F1', 'Reconcile');


    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa`";

    }else{
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_mpesa` WHERE  `reconcile` =$reconciled";
    }

    $result = DB::instance()->executeSQL($sql);
    $x = 1;
    while( $row = $result->fetch_assoc()){
        $x++;
        $sheet->setCellValue('A'.$x,  $row['TransactionName']);
        $sheet->setCellValue('B'.$x,  $row['Comments']);
        $sheet->setCellValue('C'.$x,  $row['AccountNumber']);
        $sheet->setCellValue('D'.$x,  $row['Credit']);
        $sheet->setCellValue('E'.$x,  $row['des']);
        $sheet->setCellValue('F'.$x,  $row['reconcileDesc']);

    }



    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

}
if($function=="generateSpreadSheetATMCB"){

    header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
    header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Transaction Name');
    $sheet->setCellValue('B1', 'Comments');
    $sheet->setCellValue('C1', 'Account Number');
    $sheet->setCellValue('D1', 'Credit');
    $sheet->setCellValue('E1', 'des');
    $sheet->setCellValue('F1', 'Reconcile');


    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm`";

    }else{
        $sql ="SELECT `TransactionName`,`Comments`,`AccountNumber`,`Credit`,`des`,`reconcileDesc` FROM `core_banking_atm` WHERE  `reconcile` =$reconciled";
    }


    $result = DB::instance()->executeSQL($sql);
    $x = 1;
    while( $row = $result->fetch_assoc()){
        $x++;
        $sheet->setCellValue('A'.$x,  $row['TransactionName']);
        $sheet->setCellValue('B'.$x,  $row['Comments']);
        $sheet->setCellValue('C'.$x,  $row['AccountNumber']);
        $sheet->setCellValue('D'.$x,  $row['Credit']);
        $sheet->setCellValue('E'.$x,  $row['des']);
        $sheet->setCellValue('F'.$x,  $row['reconcileDesc']);

    }



    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

}



//EXTERNAL REPORT
if($function =="generatePDFmPESAExternal"){
    $reconciled = $_REQUEST['reconciled'];

    if($reconciled==2){
        $sql ="SELECT `CompletionTime`,`ReceiptNo`,`PaidIn`,`Details`,`des`,`reconcileDesc` FROM `mpesa_transaction`";
    }else{
        $sql ="SELECT `CompletionTime`,`ReceiptNo`,`PaidIn`,`Details`,`des`,`reconcileDesc` FROM `mpesa_transaction` WHERE `reconcile` ='$reconciled' ";
    }


    $result = DB::instance()->executeSQL($sql);

    $header = DB::instance()->executeSQL("SHOW COLUMNS FROM mpesa_transaction
                                       WHERE FIELD IN('CompletionTime','ReceiptNo','PaidIn','Details','des','reconcileDesc')");


    $display_heading = array('CompletionTime'=> 'CompletionTime', 'ReceiptNo'=> 'ReceiptNo','Details'=> 'Details','des'=> 'des','reconcileDesc'=> 'Reconcile',);

    $pdf = new PDF();
//header
    $pdf->AddPage('Horizontal');
//foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','B',8);
    foreach($header as $heading) {
        $pdf->Cell(47,12,$display_heading[$heading['Field']],1);
    }
    foreach($result as $row) {
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(47,12,$column,1);
    }
    $pdf->Output();

}
if($function =="generatePDFaTMCExternal"){

    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` ";

    }else{
        $sql ="SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` WHERE  `reconcile` =$reconciled";
    }


    $result = DB::instance()->executeSQL($sql);

    $header = DB::instance()->executeSQL("SHOW COLUMNS FROM atm_transaction
                                       WHERE FIELD IN('cardNumber','amount','RRN','account','des','reconcileDesc')");


    $display_heading = array('cardNumber'=> 'cardNumber', 'amount'=> 'amount','RRN'=> 'RRN','account'=> 'account','des'=> 'des',
        'reconcileDesc'=> 'reconcile');

    $pdf = new PDF();
//header
    $pdf->AddPage('Horizontal');
//foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','B',8);
    foreach($header as $heading) {
        $pdf->Cell(47,12,$display_heading[$heading['Field']],1);
    }
    foreach($result as $row) {
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(47,12,$column,1);
    }
    $pdf->Output();

}

if($function=="generateSpreadSheetMpesaExternal"){

    header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
    header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'CompletionTime');
    $sheet->setCellValue('B1', 'Details');
    $sheet->setCellValue('C1', 'ReceiptNo');
    $sheet->setCellValue('D1', 'PaidIn');
    $sheet->setCellValue('E1', 'des');
    $sheet->setCellValue('F1', 'reconcile');


    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `CompletionTime`,`Details`,`ReceiptNo`,`PaidIn`,`des`,`reconcileDesc` FROM `mpesa_transaction`";

    }else{
        $sql ="SELECT `CompletionTime`,`Details`,`ReceiptNo`,`PaidIn`,`des`,`reconcileDesc` FROM `mpesa_transaction` WHERE  `reconcile` =$reconciled";
    }

    $result = DB::instance()->executeSQL($sql);
    $x = 1;
    while( $row = $result->fetch_assoc()){
        $x++;
        $sheet->setCellValue('A'.$x,  $row['CompletionTime']);
        $sheet->setCellValue('B'.$x,  $row['Details']);
        $sheet->setCellValue('C'.$x,  $row['ReceiptNo']);
        $sheet->setCellValue('D'.$x,  $row['PaidIn']);
        $sheet->setCellValue('E'.$x,  $row['des']);
        $sheet->setCellValue('F'.$x,  $row['reconcileDesc']);

    }



    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

}
if($function=="generateSpreadSheetATMExternal"){

    header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
    header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'cardNumber');
    $sheet->setCellValue('B1', 'amount');
    $sheet->setCellValue('C1', 'RRN');
    $sheet->setCellValue('D1', 'account');
    $sheet->setCellValue('E1', 'des');
    $sheet->setCellValue('F1', 'Reconcile');


    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction`";

    }else{
        $sql ="SELECT `cardNumber`,`amount`,`RRN`,`account`,`des`,`reconcileDesc` FROM `atm_transaction` WHERE  `reconcile` =$reconciled";
    }


    $result = DB::instance()->executeSQL($sql);
    $x = 1;
    while( $row = $result->fetch_assoc()){
        $x++;
        $sheet->setCellValue('A'.$x,  $row['cardNumber']);
        $sheet->setCellValue('B'.$x,  $row['amount']);
        $sheet->setCellValue('C'.$x,  $row['RRN']);
        $sheet->setCellValue('D'.$x,  $row['account']);
        $sheet->setCellValue('E'.$x,  $row['des']);
        $sheet->setCellValue('F'.$x,  $row['reconcileDesc']);

    }



    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

}


//Kocela REPORTS
if($function =="generatePDFmPESAKocela"){
    $reconciled = $_REQUEST['reconciled'];

    if($reconciled==2){
        $sql ="SELECT  `InternalRef`, `TransactionRef`, `PhoneNumber`, `Service`, `Amount`, `UserAccount`, `Destination`, `status`, `StatusMessage`, `DateCreated`, `des`, `reconcileDesc` FROM `kocela_transactions` ";
    }else{
        $sql ="SELECT  `InternalRef`, `TransactionRef`, `PhoneNumber`, `Service`, `Amount`, `UserAccount`, `Destination`, `status`, `StatusMessage`, `DateCreated`, `des`, `reconcileDesc` FROM `kocela_transactions` WHERE `reconcile`='$reconciled'";
    }


    $result = DB::instance()->executeSQL($sql);

    $header = DB::instance()->executeSQL("SHOW COLUMNS FROM kocela_transactions
                                       WHERE FIELD IN('InternalRef','TransactionRef','PhoneNumber','Service','Amount','UserAccount','Destination','status','StatusMessage','DateCreated','des','reconcileDesc')");


    $display_heading = array('InternalRef'=> 'InternalRef', 'TransactionRef'=> 'TransactionRef','PhoneNumber'=> 'PhoneNumber','Service'=> 'Service','Amount'=> 'Amount','UserAccount'=>'UserAccount','Destination'=>'Destination','status'=>'status',
        'StatusMessage'=>'StatusMessage','DateCreated'=>'DateCreated','des'=>'des','reconcileDesc'=>'reconcileDesc');

    $pdf = new PDF();
//header
    $pdf->AddPage('Horizontal');
//foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','B',8);
    foreach($header as $heading) {
        $pdf->Cell(47,12,$display_heading[$heading['Field']],1);
    }
    foreach($result as $row) {
        $pdf->Ln();
        foreach($row as $column)
            $pdf->Cell(47,12,$column,1);
    }
    $pdf->Output();

}
if($function=="generateSpreadSheetMpesaKocela"){

    header('Content-type: application/vnd.ms-excel');

// It will be called file.xls
    header('Content-Disposition: attachment; filename="BankReconciliationReport.xlsx"');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'InternalRef');
    $sheet->setCellValue('B1', 'TransactionRef');
    $sheet->setCellValue('C1', 'PhoneNumber');
    $sheet->setCellValue('D1', 'Service');
    $sheet->setCellValue('E1', 'Amount');
    $sheet->setCellValue('F1', 'UserAccount');
    $sheet->setCellValue('G1', 'Destination');
    $sheet->setCellValue('H1', 'status');
    $sheet->setCellValue('I1', 'StatusMessage');
    $sheet->setCellValue('J1', 'DateCreated');
    $sheet->setCellValue('K1', 'des');
    $sheet->setCellValue('L1', 'reconcileDesc');


    $reconciled = $_REQUEST['reconciled'];
    if($reconciled==2){
        $sql ="SELECT  `InternalRef`, `TransactionRef`, `PhoneNumber`, `Service`, `Amount`, `UserAccount`, `Destination`, `status`, `StatusMessage`, `DateCreated`, `des`, `reconcileDesc` FROM `kocela_transactions` ";
    }else{
        $sql ="SELECT  `InternalRef`, `TransactionRef`, `PhoneNumber`, `Service`, `Amount`, `UserAccount`, `Destination`, `status`, `StatusMessage`, `DateCreated`, `des`, `reconcileDesc` FROM `kocela_transactions` WHERE `reconcile`='$reconciled'";
    }


    $result = DB::instance()->executeSQL($sql);
    $x = 1;
    while( $row = $result->fetch_assoc()){
        $x++;
        $sheet->setCellValue('A'.$x,  $row['InternalRef']);
        $sheet->setCellValue('B'.$x,  $row['TransactionRef']);
        $sheet->setCellValue('C'.$x,  $row['PhoneNumber']);
        $sheet->setCellValue('D'.$x,  $row['Service']);
        $sheet->setCellValue('E'.$x,  $row['Amount']);
        $sheet->setCellValue('F'.$x,  $row['UserAccount']);
        $sheet->setCellValue('G'.$x,  $row['Destination']);
        $sheet->setCellValue('H'.$x,  $row['status']);
        $sheet->setCellValue('I'.$x,  $row['StatusMessage']);
        $sheet->setCellValue('J'.$x,  $row['DateCreated']);
        $sheet->setCellValue('K'.$x,  $row['des']);
        $sheet->setCellValue('L'.$x,  $row['reconcileDesc']);

    }



    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");

}


function toInt($str)
{
    return (int)preg_replace("/\..+$/i", "", preg_replace("/[^0-9\.]/i", "", $str));
}

function diff($v1, $v2) {
    return ($v1-$v2) < 0 ? (-1)*($v1-$v2) : ($v1-$v2);
}

function formatcurrency($input)
{
    setlocale(LC_MONETARY,"en_US");
    return str_replace("USD","KES",money_format("%i", $input));



}


?>