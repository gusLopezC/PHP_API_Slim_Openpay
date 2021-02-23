<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require(dirname(__FILE__) . '/../libs/Openpay/Openpay.php');
// Import Monolog classes into the global namespace
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$container = $app->getContainer();

$container['cache'] = function () {
	return new \Slim\HttpCache\CacheProvider();
};

$container["logger"] = function ($c) {
	// create a log channel
	$log = new Logger("api");
	$log->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", Logger::INFO));

	return $log;
};

$app->add(new \Slim\HttpCache\Cache('private', 300, true));

/**
 * This method restricts access to addresses. <br/>
 * <b>post: </b>To access is required a valid token.
$app->add(new \Slim\Middleware\JwtAuthentication([
	// The secret key
	"secret" => SECRET,
	"rules" => [
		new \Slim\Middleware\JwtAuthentication\RequestPathRule([
			// Degenerate access to "/webresources"
			"path" => "/webresources",
			// It allows access to "login" without a token
			"passthrough" => [
				"/webresources/mobile_app/ping",
				"/webresources/mobile_app/login",
				"/webresources/mobile_app/register",
				"/webresources/mobile_app/validate"
			]
		])
	]
]));
 */

/**
 * This method settings CORS requests
 *
 * @param	\Psr\Http\Message\ServerRequestInterface	$request	PSR7 request
 * @param	\Psr\Http\Message\ResponseInterface      	$response	PSR7 response
 * @param	callable                                 	$next     	Next middleware
 *
 * @return	\Psr\Http\Message\ResponseInterface
 */
$app->add(function (Request $request, Response $response, $next) {
	$response = $next($request, $response);
	// Access-Control-Allow-Origin: <domain>, ... | *
	$response = $response->withHeader('Access-Control-Allow-Origin', '*')
		// ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
	return $response;
});

/**
 * This method creates an urls group. <br/>
 * <b>post: </b>establishes the base url "/public/webresources/mobile_app/".
 */
$app->group("/webresources/mobile_app", function () use ($app) {
	/**
	 * This method is used for testing the api.<br/>
	 *
	 * @param	\Psr\Http\Message\ServerRequestInterface	$request	PSR7 request
	 * @param	\Psr\Http\Message\ResponseInterface      	$response	PSR7 response
	 *
	 * @return	string
	 */
	$app->get("/ping", function (Request $request, Response $response) {
		$name = "Hola";
		$subject = "Confirmación de pago";

		Mailer::send("guslopezcallejas@gmail.com", $name, $subject);

		return "pong";
	});


	/**
	 * Este metodo realiza la petición a Openpay para realizarun cargo por tarjeta.
	 *
	 * @param	\Psr\Http\Message\ServerRequestInterface	$request	PSR7 request
	 * @param	\Psr\Http\Message\ResponseInterface      	$response	PSR7 response
	 *
	 * @return	\Psr\Http\Message\ResponseInterface
	 */
	$app->post("/Openpay_charge", function (Request $request, Response $response) {
		/** @var string $quote - The text of post */
		
		$user_id = $request->getParam("user_id");
		$customer_name = null;
		$customer_email = null;

		$conn = PDOConnection::getConnection();
		$openpay = Openpay::getInstance('mdrhnprmsmxkgxtegzhk', 'sk_c71babd865fd420b94bc588a8585c122');

		$sql = "SELECT	*
					FROM	app_acceso
					WHERE	clav_re =  $user_id";
				$stmt = $conn->prepare($sql);
				$stmt->bindParam(":clav_re", $user_id);
				$result = $stmt->execute();
				$query = $stmt->fetchObject();
				// Return the result
		
		if($query->openpay_customer){
			$customer_id = $query->openpay_customer;
			$customer_name = $query->nomb_ac;	
			$customer_email = $query->corr_ac;
		}else{
			$customer = array(
				'name' => $query->nomb_ac,
				'phone_number' => $query->tele_ac,
				'email' => $query->corr_ac,
			);
			try{
				$customer = $openpay->customers->add($customer);
				
			}catch (PDOException $e) {
				$this["logger"]->error("DataBase Error: {$e->getMessage()}");
			}
			$customer_name = $customer->name;	
			$customer_email = $customer->email;
			try{
				$customer_id = $customer->id;
					

				$sql = "UPDATE	app_acceso
				SET		openpay_customer = '$customer_id'
				WHERE	clav_re = $user_id";
				$stmt = $conn->prepare($sql);
				$result = $stmt->execute();
				$query = $stmt->fetchObject();
			}catch (PDOException $e) {
				$this["logger"]->error("DataBase Error: {$e->getMessage()}");
			}
		
		}
		$chargeRequest = array(
			'method' => 'card',
			'source_id' => $request->getParam("source_id"),
			'amount' => $request->getParam("amount"),
			'currency' => 'MXN',
			'description' => 'Cargo inicial a mi merchant',
			'device_session_id' => $request->getParam("device_session_id"),
		);

		$charge = null;
		$errorMsg = null;
		$errorCode = null;
		$email = null;

		try{
			$customer = $openpay->customers->get($customer_id);
			$charge = $customer->charges->create($chargeRequest);
		} catch (Exception $e) {
			$errorMsg = $e->getMessage();
			$errorCode =  $e->getErrorCode();
		}
		if ($errorMsg !== null || $errorCode !== null) {
			
			return $response = $response->withHeader("Content-Type", "application/json")
				->withStatus(400, "OK")
				->withJson([
					'errorMsg' => $errorMsg,
					"errorCode" => $errorCode
					]);
		} else {
			$conn = PDOConnection::getConnection();

			try{
			// Gets the user into the database
			$sql = "INSERT INTO	orders (order_number, transaction_id, user_id, total_amount, payment_method, payment_status, status)
			VALUES		(01,'$charge->id',$user_id,$charge->amount,'openpay','$charge->status','$charge->status')";
			$stmt = $conn->prepare($sql);
			$result = $stmt->execute();
			$query = $stmt->fetchObject();

		}catch (PDOException $e) {
			$this["logger"]->error("DataBase Error: {$e->getMessage()}");
		}
				$subject = "Confirmación de pago";

				$email = Mailer::send($customer_email, $customer_name, $charge);

			return $response = $response->withHeader("Content-Type", "application/json")
				->withStatus(201, "OK")
				->withJson([
					'id' => $charge->id,
					'status' => $charge->status,
					'email' => $email,
					 ]);
		}

	});
	
});


