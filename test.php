<?php
/**
 * Created by PhpStorm.
 * User: andrei
 * Date: 11/21/14
 * Time: 15:01 PM
 */





#
#/TrustPilotData MODEL/Object, this is where we define our TrustPilot transit data
class TrustPilotData
{
	#
	#/Property that indicates whether the model structure is ok
	static $VALIDITY = FALSE;
	#
	#/Property that holds the errors encountered while building the Object
	static $EXCEPTION = FALSE;
	#
	#/Property in which we define the whole object contents, the type each property, if it is a required property, etc
	public $DATA_KEYS = 	array(
		'recipientName'		=> array('validator' => 'string', 	'required' => true),
		'recipientEmail' 	=> array('validator' => 'email', 	'required' => true),
		'referenceId'		=> array('validator' => 'int', 		'required' => true),
		'templateId'		=> array('validator' => 'int', 		'required' => true),
		'locale'			=> array('validator' => 'custom01', 'required' => true),
		'senderEmail'		=> array('validator' => 'email', 	'required' => true),
		'senderName'		=> array('validator' => 'string', 	'required' => true),
		'replyTo'			=> array('validator' => 'email', 	'required' => true),
		'preferredSendTime'	=> array('validator' => 'custom02', 'required' => true),
	);

	#
	#/Injects Model properties & checks for data validity throwing exceptions with codes for better
	#/frontend error handling
	public function fillModel()
	{
		new ModelFormatter($this);
		new TrustPilotDataValidator($this);
	}

	#
	#/Injects into model a property named $response that you can use to return to an API
	public function prepareResponse()
	{
		new ResponseInjector($this);
	}
}


#
#/Object filler, populates any object with data
class ModelFormatter
{
	var $THE_MODEL = null;

	#
	#/class initializer, prepares POST data for fillable model
	public function __construct($model)
	{
		$this->THE_MODEL = $model;

		foreach ($model->DATA_KEYS as $key => $properties)
		{
			$this->fillKeyData($key, $properties);
		}
	}

	#
	#/Fills any model with required data/keys
	#/If the key is not required, or present it will assign a null value
	#/@input: $key:string, $properties:array(validator,required)
	#/@output: NIL
	private function fillKeyData($key, $properties)
	{
		if ($properties['required']):
			if ( !isset($_POST[$key]) )
				throw new \Exception('Required Key Missing', '001');
		endif;

		$this->THE_MODEL->$key = ( isset($_POST[$key]) ) ? trim($_POST[$key]) : null;
	}
}

#
#/TrustPilotData Custom Validator - should be a 3'rd child after inherits an interface and an
#/abstract class with default validators, but not really a lot of time to do this.
class TrustPilotDataValidator
{

	public $AVAILABLE_VALIDATORS = 	array (
										'int' 		=> 	'validateInteger',
										'email'		=>	'validateEmail',
										'string'	=> 	'validateString',
										'custom01'	=>	'customValidation01',
										'custom02'	=>	'customValidation02'
									);

	#
	#/All the locales supported by Trustpilot. Currently:
	#/this information should have a ini file that is update by a cron once a week or something like that
	#/keeping it in DB will only occupy resources. I'm defining it here for easy use
	public static $TRUSTPILOT_LOCALE_SUPPORT = array(
											'da-DK',
											'de-DE',
											'en-GB',
											'es-ES',
											'fi-FI',
											'fr-FR',
											'it-IT',
											'nb-NO',
											'nl-NL',
											'sv-SE'
											);


	#
	#/Validates TrustPilot Model Data
	#@data:(any),@validator:string
	function __construct($MODEL)
	{
		foreach($MODEL->DATA_KEYS as $key => $properties):
			if ( array_key_exists ($properties['validator'], $this->AVAILABLE_VALIDATORS) ):
				$is_valid = $this->{$this->AVAILABLE_VALIDATORS[$properties['validator']]}($MODEL->$key);
			else:
				throw new \Exception("Cannot validate value with {$properties['validator']} type", '003');
			endif;

			if ( $is_valid === false )
				throw new \Exception("Value for Key: $key is not a valid {$properties['validator']}", '002');
		endforeach;

		$MODEL->VALIDITY = TRUE;
	}

	#
	#/@$input: string ( should contain an email address )
	#/@return: bool ( true: $input is a valid email address, false: otherwise )
	static function validateEmail($input)
	{
		return filter_var($input, FILTER_VALIDATE_EMAIL);
	}


	#
	#/@$input: string/integer ( should contain an integer )
	#/@return: bool ( true: $input is a valid integer, false: otherwise )
	static function validateInteger($input)
	{
		return ctype_digit($input);
	}

	#
	#/@$input: string ( A-Z ' & - )
	#/@return: bool ( true: $input is a valid string , false: otherwise )
	static function validateString($input)
	{
		#
		#/Since everything in PHP is basically a string I need to know more details to validate such a feature.
		#/For EG: is_string( function from PHP will not validate a name like "Mr. O'Brian Dan-Anderson" because
		#/characters like [',.,-] are not valid in is_string() context
		if ( strlen(str_replace(" ", "", $input)) )
			return true;
		else
			return false;
	}

	#
	#/@input: sting
	#/@return: bool ( true: it is a valid locale from TrustPilot, false: otherwise )
	static function customValidation01($input)
	{
		return  in_array($input, self::$TRUSTPILOT_LOCALE_SUPPORT) !== FALSE ;
	}

	#
	#/@input: sting containing date
	#/@return: bool ( true: it is a valid date with TrustPilot format, false: otherwise )
	static function customValidation02($input)
	{
		return DateTime::createFromFormat('Y-m-d\TH:i:s', $input) !== FALSE;
	}



}

#
#/Class that formats response for a given MODEL/Object
class ResponseInjector
{
	var $MODEL;

	#
	#/Format response
	public function __construct($MODEL)
	{
		$this->MODEL = $MODEL;
		$MODEL->response =	array(
								'success' 	=>	$MODEL::$VALIDITY,
								'response'	=>	$this->formatObjectInformation()
							);
	}

	function formatObjectInformation()
	{
		$responseData = array();
		foreach( $this->MODEL->DATA_KEYS as $keyname => $property )
			$responseData[$keyname] = $this->MODEL->$keyname;

		if ( !$this->MODEL->VALIDITY )
			$responseData = $this->MODEL->EXCEPTION;

		return $responseData;
	}
}







#
#/How to use all of the above


/*
int main()
{
*/


	$TrustPilotData = new TrustPilotData();

	try
	{
		$TrustPilotData->fillModel();
	}
	catch ( \Exception $e)
	{
		$TrustPilotData->EXCEPTION = array(
			'message' 	=> $e->getMessage(),
			'code'		=> $e->getCode()
		);
	}


	$TrustPilotData->prepareResponse();



	echo jsonToReadable(json_encode($TrustPilotData->response));

/*
}
*/












































#
#HELPER FUNCTIONS
function jsonToReadable($json){
	$tc = 0;        //tab count
	$r = '';        //result
	$q = false;     //quotes
	$t = "\t";      //tab
	$nl = "\n";     //new line

	for($i=0;$i<strlen($json);$i++){
		$c = $json[$i];
		if($c=='"' && $json[$i-1]!='\\') $q = !$q;
		if($q){
			$r .= $c;
			continue;
		}
		switch($c){
			case '{':
			case '[':
				$r .= $c . $nl . str_repeat($t, ++$tc);
				break;
			case '}':
			case ']':
				$r .= $nl . str_repeat($t, --$tc) . $c;
				break;
			case ',':
				$r .= $c;
				if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
				break;
			case ':':
				$r .= $c . ' ';
				break;
			default:
				$r .= $c;
		}
	}
	return $r;
}