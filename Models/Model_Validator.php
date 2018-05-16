<?php
namespace Famous\Models;
use Famous\Core\Model as Model;
use Famous\Lib\Managers\HelpManager;
use Famous\Lib\Utils\Constant as Constant;
use Famous\Lib\Common\Manager as Manager;
use Famous\Lib\Common\Response as Response;
use Famous\Lib\Utils\DB as DB;
use Famous\Lib\Utils\Helper;
use \PDO as PDO;
use \Exception as Exception;
/**
 * Created by PhpStorm.
 * User: ruboss
 * Date: 08/21/2016
 * Time: 21:53
 */

use ReceiptValidator\GooglePlay\Validator as PlayValidator;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

class Model_Validator extends Model
{
    public function verifyPlay(){


        $data = new Response("verify", Constant::ERR_STATUS, "No depth value");
        $str = "";

        $headers = Helper::getallheaders();
        if (strpos($headers["Content-Type"],"application/json") !== false){
            $str =  file_get_contents("php://input");
            file_put_contents("comingfile", $str);
            $product = json_decode($str);

        }else if(!empty($_REQUEST['receipt'])){
            $receipt = $_REQUEST['receipt'];
            file_put_contents("in.txt", $receipt);
            $product = json_decode($receipt);
        }

        //$product = json_decode('{"id":"turbos_3","alias":"Turbo red","type":"paid subscription","state":"approved","title":"Turbo red (Meteor Boost)","description":"You will get 1 month turbo on your account","price":"99,00 грн.","currency":"UAH","loaded":true,"canPurchase":false,"owned":false,"downloading":false,"downloaded":false,"transaction":{"type":"android-playstore","id":"GPA.1348-9359-1300-45774","purchaseToken":"kogkipnegfkjnlakhkidbhfm.AO-J1OwKmNEKTRE5IxoxXhKalo3AnbH4IUw95KyEJzCHBCT__kibAWByIgHRjFuhx69gHaWks7sjxI1xTkad7-u2C5lErjXx-a_LbK0SbWvqmwhOXYsfnJLE0pcMUW2wSFSY2ToELtoq","receipt":"{\"orderId\":\"GPA.1348-9359-1300-45774\",\"packageName\":\"com.ruboss.meteor_boost\",\"productId\":\"turbos_3\",\"purchaseTime\":1472155511326,\"purchaseState\":0,\"purchaseToken\":\"kogkipnegfkjnlakhkidbhfm.AO-J1OwKmNEKTRE5IxoxXhKalo3AnbH4IUw95KyEJzCHBCT__kibAWByIgHRjFuhx69gHaWks7sjxI1xTkad7-u2C5lErjXx-a_LbK0SbWvqmwhOXYsfnJLE0pcMUW2wSFSY2ToELtoq\",\"autoRenewing\":true}","signature":"e8rCze7w1bwmOR9dfEa1aZ5BbYn+oCemXJ97sP56gYrSgsLzIF/EXRHBM3FPppIO+3RRm4BK20wMxAkfVPzFobn/wbZeVim6TCHQYsQtwuFDCnv1/7n0/D0AXNoT2m/h1M+lmzn3wxzRkYLi+ByKtChuv6lMrl4amHc7sDKBF9QrnBqhtllbCbOoveyHgyH/LS++vTXUbeoZ/HtgrhCI/s7UqAfgWrllnJ03+1/Xt0u3I2ZzXGJjF4a/njCJZBiHBchjKvnxAiD9QUV4HHxDGCG8DgXGAiKTyDUvC5Uba0A6Baqhr3Jx15YaZifwCaInqao2uVlNE44OHqVDA53Esw=="},"valid":true}');
        //$product = json_decode('{"id":"pack_3","alias":"x5000","type":"consumable","state":"approved","title":"5000 diamonds (Meteor Boost)","description":"You will take 5000 diamonds on your account Meteor Boost","price":"84,99 грн.","currency":"UAH","loaded":true,"canPurchase":false,"owned":false,"downloading":false,"downloaded":false,"transaction":{"type":"android-playstore","purchaseToken":"fghkpchelknnkbaecmfkpakd.AO-J1OwdANfsOgzAZAFylFKO4JshdQ6xjMVoCbpjKffr-Y98mV8KkYUnmCBqmwTTc0q5-AU-lp_ZMoa3mtEu3lbSEvPknObtI4w-3NQqvGOYXBDt51FmjD4","receipt":"{\"packageName\":\"com.ruboss.meteor_boost\",\"productId\":\"pack_3\",\"purchaseTime\":1473493897118,\"purchaseState\":0,\"purchaseToken\":\"fghkpchelknnkbaecmfkpakd.AO-J1OwdANfsOgzAZAFylFKO4JshdQ6xjMVoCbpjKffr-Y98mV8KkYUnmCBqmwTTc0q5-AU-lp_ZMoa3mtEu3lbSEvPknObtI4w-3NQqvGOYXBDt51FmjD4\"}","signature":"WR/gXseR4cN96O8vk0V5/uIrSqJLtbKFA/nyOujI2hazUNSTtSZm45SD7FvISC4unlOfSNlaIX+1Jqg1bSTIC8260qhfDf7/d4OA6EpvW2FLf4XZheQJVIa1Zlv4s/d/oAs9/VkarLDxzvyGm3E+7I9E5iXTbE9AL9QN1w94OEvb/zEBUQWgMYqsxpXC34DWnx9jp0+HBFQpmIm2E2el7uLySpAh6TiSjuZRGHo58gYTyr6P/OlDsMTXHsuRVtP+EiKwE9qFiejV8f1+v2n+68XjNet+BKspZdHTDTp4VyhG/rC7sVxbqgC44wO0ghC2i9PX0iGB7OBlOZfHt3Ntsg=="},"valid":true}');
        //$product = json_decode('{"id":"pack_4","alias":"x10000","type":"consumable","state":"approved","title":"10000 diamonds (Meteor Boost)","description":"You will take 10000 diamonds on your account Meteor Boost","price":"129,99 грн.","currency":"UAH","loaded":true,"canPurchase":false,"owned":false,"downloading":false,"downloaded":false,"transaction":{"type":"android-playstore","purchaseToken":"mgnnjbldhagoidcelmlmkpmf.AO-J1OwGFK9IFYEmwrvA7PYyaosHZyourzsF6a2M1FUI0NMNb3T6Rb4KZadNF92Ti2pK_1vy70yu6i3_HPolNjKsjzdFYyu4gmmNwfKccfZxscYB7YFVwjw","receipt":"{\"packageName\":\"com.ruboss.meteor_boost\",\"productId\":\"pack_4\",\"purchaseTime\":1473530199382,\"purchaseState\":0,\"purchaseToken\":\"mgnnjbldhagoidcelmlmkpmf.AO-J1OwGFK9IFYEmwrvA7PYyaosHZyourzsF6a2M1FUI0NMNb3T6Rb4KZadNF92Ti2pK_1vy70yu6i3_HPolNjKsjzdFYyu4gmmNwfKccfZxscYB7YFVwjw\"}","signature":"WaDHoM0xGskc9YYDu1Mx+UFDwP1ecIEkuSxw8Z7q4GwP/jH7MnoVf0pydKGv9zanr/pvmb44/V2JsxOV1StVO9/zCui4tTYtcGqHe/rtioQquRA26rhO/8TUciDo/1DH9wHzzvlLOm+GS6u9mkf4lYdqgS+pKR/dRjiZCX6NjSA/Mv/Xac/RXw5xG5DI73nHipWBBexCxwIsjxfN+hstlMfvu1KDNzsMFv/KhpDs/m6YbCdNdoUnG2s6ywpo9hL587WlNBPH+WIyQYpQaPSXnpSOu8NsnLu7RxY4PQXF0Zs+3V5Q5QB37y90loGeraAJjshrRovpA7D4N4DfquceQA=="},"valid":true}');

        //$product = json_decode('{"transaction":{"receipt":"{\"packageName\":\"com.crabsalo.malo\",\"productId\":\"pack_f_7\",\"purchaseTime\":1494247984785,\"purchaseState\":0,\"purchaseToken\":\"iijnhbjclgaopdleadknngej.AO-J1Oy1IMYOJGJ4MHbgVNT_-jbReSb4grS10oeBNeNIO6SNRwm-Cuguh9am0RmajEblBk9aQMMP2NAZMJXe6b0U2y0eygkeJFMbheIGAnLnu37pTTEsJCE\"}"}}');

        if($product){
            $data = $this->marketReceipt($product);
        }
        if($data->getStatus() == Constant::OK_STATUS){
            $data = array("ok"=>true, "data"=>"ok");
        }else{
            $data = array("error"=>true, "code"=>(6777e3+17));
        }
        echo json_encode($data);
       // file_put_contents("res", json_encode($data));
        exit;
        //return $data;
    }

    public function verifyItunes(){


        $data = new Response("verify", Constant::ERR_STATUS, "No depth value");
        $str = "";

        $headers = getallheaders();
        if (strpos($headers["Content-Type"],"application/json") !== false){
            $str =  file_get_contents("php://input");
            //ile_put_contents("iospurchase", $str);
            $product = json_decode($str);

        }

        if($product){
            $data = $this->storeReceipt($product);
        }

        if($data->getStatus() == Constant::OK_STATUS){
            $data = array("ok"=>true, "data"=>"ok");
        }else{
            $data = array("error"=>true, "code"=>(6777e3+17));
        }
        echo json_encode($data);
        // file_put_contents("res", json_encode($data));
        exit;
        //return $data;
    }

    public function storeReceipt($product){
        //var_dump($product);exit;
        $transaction = $product->transaction;
        if(!$transaction->transactionReceipt){
            return  $data = new Response("verify", Constant::ERR_STATUS, "No transaction receipt");
        }
        $receiptBase64Data = $transaction->transactionReceipt;

        $user_id = Manager::$user->getId();
        $platform = Manager::$user->getPlatform();

        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);        //ENDPOINT_PRODUCTION ENDPOINT_SANDBOX

        try {
            $validator->setReceiptData($receiptBase64Data);
            if($product->type == "paid subscription"){
                $validator->setSharedSecret(Constant::IOS_ITUNES_SECRET);
            }
            $response = $validator->validate();
        } catch (Exception $e) {
            //echo 'got error = ' . $e->getMessage() . PHP_EOL;
            $data = new Response("verify", Constant::ERR_STATUS, "Can not take data");
        }

        if($response){
            if ($response->isValid()) {
                $receipt = $response->getReceipt();
                //var_dump($receipt);
                foreach ($response->getPurchases() as $purchase) {

                    $packageName = $response->getBundleId();
                    $orderId = $receiptBase64Data;
                    $productId = $purchase['product_id'];
                    $type = HelpManager::getProductType($productId);
                    $purchaseTime = time();
                    $purchaseToken = $purchase['transaction_id'];
                    //echo $orderId . " " . $packageName . " " . $productId . " " . $purchaseTime . " " . $purchaseToken;
                   // echo "<br>--------------------</br>";

                    $status = Constant::INIT_SUBSCRIBE_STATUS;


                    $db = DB::getInstance();
                    $dbh = $db->getDBH();
                    if($type == Constant::CONSUMABLE_TYPE){
                        $stmt = $dbh->prepare("INSERT INTO `".Constant::PURCHASES_TABLE."` (order_id, product_id, purchase_time, purchase_token, type, user_id, status, package_name, platform)
								 VALUES (:orderId, :productId, :purchaseTime, :purchaseToken, :type, :user_id, :status, :package_name, :platform)");
                    }else{
                        $startTime = (int)($receipt['purchase_date_ms'] / 1000);
                        $expiryTime = (int)($receipt['expires_date'] / 1000);
                        $stmt = $dbh->prepare("INSERT INTO `".Constant::SUBSCRIBES_TABLE."` (order_id, product_id, purchase_time, purchase_token, type, user_id, status, start_time, expiry_time, package_name, platform)
								 VALUES (:orderId, :productId, :purchaseTime, :purchaseToken, :type, :user_id, :status, :startTime, :expiryTime, :package_name, :platform)");
                        $stmt->bindParam(":startTime", $startTime, PDO::PARAM_INT);
                        $stmt->bindParam(":expiryTime", $expiryTime, PDO::PARAM_INT);
                    }

                    $stmt->bindParam(":orderId",$orderId);
                    $stmt->bindParam(":package_name", $packageName);
                    $stmt->bindParam(":productId",$productId);
                    $stmt->bindParam(":purchaseTime",$purchaseTime, PDO::PARAM_INT);
                    $stmt->bindParam(":platform",$platform, PDO::PARAM_INT);
                    $stmt->bindParam(":purchaseToken",$purchaseToken);
                    $stmt->bindParam(":type",$type);
                    $stmt->bindParam(":user_id",$user_id, PDO::PARAM_INT);
                    $stmt->bindParam(":status",$status, PDO::PARAM_INT);

                   // echo "INSERT INTO `".Constant::PURCHASES_TABLE."` (order_id, product_id, purchase_time, purchase_token, type, user_id, status, package_name)
					//			 VALUES ($orderId, $productId, $purchaseTime, $purchaseToken, $type, $user_id, $status, $packageName)";

                    //need to change structure to select -> insert for unique key `purchase_token`
                    try{
                        $stmt->execute();
                    }catch(Exception $e){

                    }
                }


                //echo 'Receipt is valid.' . PHP_EOL;
                //echo 'Receipt data = ' . print_r($response->getReceipt()) . PHP_EOL;
                $data = new Response("verify", Constant::OK_STATUS, "");
            } else {
                //echo 'Receipt is not valid.' . PHP_EOL;
                //echo 'Receipt result code = ' . $response->getResultCode() . PHP_EOL;
                $data = new Response("verify", Constant::ERR_STATUS, "Not purchased");
            }
        }else{
            $data = new Response("verify", Constant::ERR_STATUS, "Can not take data");
        }

        return $data;
    }

    public function marketReceipt($product){

        $receipt = json_decode($product->transaction->receipt);
        $packageName = $receipt->packageName;
        $productId = $receipt->productId;
        $purchaseToken = $receipt->purchaseToken;
        $purchaseTime = time();
        if($receipt->orderId){
            $orderId = $receipt->orderId;
        }else{
            $orderId = "";
        }

        $type = $product->type;
        if(!$type){
            $type = "consumable";
        }
        $user_id = Manager::$user->getId();

        $validator = HelpManager::getValidatorByPackage($packageName);

        $isSubscription = false;
        if($product->type == "paid subscription"){
            $isSubscription = true;
            $validator->setPurchaseType(PlayValidator::TYPE_SUBSCRIPTION);
        }

        try {
            $response = $validator->setPackageName($packageName)
                ->setProductId($productId)
                ->setPurchaseToken($purchaseToken)
                ->validate();
        } catch (Exception $e){
            $data = new Response("verify", Constant::ERR_STATUS, "Can not take data");
            //var_dump($e->getMessage());
            // example message: Error calling GET ....: (404) Product not found for this famous.
        }
        //file_put_contents("here1",$data);
        if($response){
            $db = DB::getInstance();
            $dbh = $db->getDBH();
            if(!$isSubscription){
                $status = $response->purchaseState;
                $stmt = $dbh->prepare("INSERT INTO `".Constant::PURCHASES_TABLE."` (order_id, product_id, purchase_time, purchase_token, type, user_id, status, package_name)
								 VALUES (:orderId, :productId, :purchaseTime, :purchaseToken, :type, :user_id, :status, :package_name)");
            }else{
                $status = Constant::INIT_SUBSCRIBE_STATUS;
                $startTime = (int)($response->startTimeMillis / 1000);
                $expiryTime = (int)($response->expiryTimeMillis / 1000);
                $stmt = $dbh->prepare("INSERT INTO `".Constant::SUBSCRIBES_TABLE."` (order_id, product_id, purchase_time, purchase_token, type, user_id, status, start_time, expiry_time, package_name)
								 VALUES (:orderId, :productId, :purchaseTime, :purchaseToken, :type, :user_id, :status, :startTime, :expiryTime, :package_name)");
                $stmt->bindParam(":startTime", $startTime, PDO::PARAM_INT);
                $stmt->bindParam(":expiryTime", $expiryTime, PDO::PARAM_INT);
            }

            $stmt->bindParam(":orderId",$orderId);
            $stmt->bindParam(":package_name", $packageName);
            $stmt->bindParam(":productId",$productId);
            $stmt->bindParam(":purchaseTime",$purchaseTime, PDO::PARAM_INT);
            $stmt->bindParam(":purchaseToken",$purchaseToken);
            $stmt->bindParam(":type",$type);
            $stmt->bindParam(":user_id",$user_id, PDO::PARAM_INT);
            $stmt->bindParam(":status",$status, PDO::PARAM_INT);
            //need to change structure to select -> insert for unique key `purchase_token`
            try{
                $stmt->execute();
            }catch(Exception $e){

            }


            if(!$isSubscription && $response->purchaseState != Constant::INIT_PURCHASE_STATUS){
                $data = new Response("verify", Constant::ERR_STATUS, "Not purchased");
            }else{
                $data = new Response("verify", Constant::OK_STATUS, "");
            }

        }else{
            $data = new Response("verify", Constant::ERR_STATUS, "Not response");
        }

        return $data;
    }

}