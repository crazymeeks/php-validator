<?php

namespace Crazymeeks\Validation;


/**
 * Validator: Provides validation functionality like of that
 * in Laravel. To use this class, you need to composer install symfony/http-foundation component
 * composer require symfony/http-foundation ~3.2
 *
 * Methods:
 * 
 * validate_image()
 * validate_mimes()
 * validate_required()
 * validate_email()
 * validate_integer()
 * validate_array()
 * validate_confirmed()
 * validate_min()
 * validate_max()
 * validate_string()
 * validate_number()
 * validate_nullable()
 * validate_required_with_all()
 * validate_strong_password()
 *
 * Usage:
 *
 * $validator  = new Validator();
 *
 * $validator->make($_POST, [
 * 		'field1' => 'required|number|min:3|max:10',
 * 		'field2' => 'string|email',
 * 		'field3' => 'array',
 * 		'field4' => 'mimes:jpg,png,svg',
 * 		'field5' => 'image',
 * 		'field6' => 'string|confirmed',
 * 		'field7.*' => 'required', # Validating array of inputs
 * 		'field8.*' => 'image|required', # Validating array of images
 * ]);
 *
 * if ($validator->fails()) {
 * 		print_r($validator->getMessage());exit;
 * }
 * 
 * @author Jeff Claud
 */

use Closure;
use Crazymeeks\Validation\ValidatorException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class Validator
{
	/**
	 * The validator error messages
	 * 
	 * @var array
	 */
	protected $messages = [];

	public static $REMOVE_EMPTY_IMG = true;

	/**
	 * The array of messages to be translated
	 * 
	 * @var array
	 */
	protected $default_messages = [
		'required'            => 'The :attribute is required',
		'email'               => 'The :attribute must be a valid email',
		'integer'             => 'The :attribute must be an integer',
		'array'               => 'The :attribute must be an array',
		'image'               => 'The :attribute must be a valid image',
		'confirmed'           => 'The :attribute must be same with :attribute_confirmation',
		'mimes'               => 'The :attribute must be a mime type of :extras',
		'min'                 => 'The :attribute must be atleast minimum of :extras',
		'max'                 => 'The :attribute must be maximum of :extras',
		'string'              => 'The :attribute must be string',
		'number'              => 'The :attribute must be a number',
		'required_with_all'   => 'The :attribute field is required when :extras is present.',
		'strong_password'     => 'Password must contain uppercase, lowercase, number, special characters and at least 8 characters',
		'url'                 => 'The :attribute must be a valid url',
	];


	private $data = [];

	/**
	 * Contains extra rules like jpg,png etc.
	 * 
	 * @var array
	 */
	private $extraRules = [];

	private $_files = [];

	/**
	 * Use when dealing with array of fields
	 *
	 * @var string|null
	 */
	private $new_field_name = null;

	/**
	 * Flag indicates that we have
	 * a nullable rules
	 * 
	 * @var bool
	 */
	private $has_nullable;


	/**
	 * Parse the data
	 * 
	 * @param  array $data
	 * 
	 * @return void
	 */
	private function parseData($data)
	{	
		
		foreach( $data as $field_name => $value ){
			
			// if $value is array
			// we are assuming this
			// is an array of inputs(name="name[]")
			if ( is_array($value)) {
				$me = $this;

				$this->new_field_name =  $field_name;
				
				$value = $me->parseData($value);
			}
			
			# Create explicit attribute
			if ( is_numeric($field_name) ) {
				
				$field_name = $this->new_field_name . '.' . $field_name;
			}

			# If the current $field_name is equal to the $this->new_field_name
			# it means that this $field_name is an array. We will append .* 
			# to this field_name then we will unset this fielname_later(field_name.*)
			# so that is will not be included in the validation
			if ( $field_name == $this->new_field_name ) {
				$field_name = $field_name . ".*";
			}
			
			$this->data[$field_name] = $value;

		}
		# Remove number keys from an array
		$keys = array_filter(array_keys($this->data), 'is_numeric');
		$out  = array_diff_key($this->data, array_flip($keys));
		if (Validator::$REMOVE_EMPTY_IMG && count($this->removeEmptyFiles()) > 0) {
			$this->parseFiles($_FILES);
		}
		$this->data = array_merge($out, $this->_files);
	}

	/**
	 * Parse $_FILES
	 *
	 * @param array $_FILES $files
	 * 
	 * @return void
	 */
	private function parseFiles(array $files)
	{

		$this->new_field_name = null;
		/**
		 * If there are files, we will merge it
		 * in the data array
		 */
		if ( isset($files) && count($files) > 0 ) {
			
			$callback = function($files, $field_name){
				
				foreach( (array) $files['size'] as $key => $filesize ){
					if ( $filesize > 0 ) {
						$tmp_name = (array) $files['tmp_name'];
						$name = (array) $files['name'];
						try{
							$this->_files["$field_name.$key"] = new UploadedFile(
								$tmp_name[$key],
								 $name[$key]
							 );
						}catch(FileNotFoundException $e){}
						
					}
				}
			};

			foreach($files as $field_name => $value){
				
				// if $value is array
				// we are assuming this
				// is an array of inputs(name="name[]")
				
				if ( is_array($value['name'])) {
					$me = $this;
					$this->new_field_name =  $field_name;
					$value = $callback($value, $field_name);
				} else {
					# Create explicit attribute
					if ( is_numeric($field_name) ) {
						$field_name = $this->new_field_name . '.' . $field_name;
					}
					# If the current $field_name is equal to the $this->new_field_name
					# it means that this $field_name is an array. We will append .* 
					# to this field_name then we will unset this fielname_later(field_name.*)
					# so that is will not be included in the validation
					if ( $field_name == $this->new_field_name ) {
						
						$field_name = $field_name . ".*";
					}
					try{
						$this->_files[$field_name] = new UploadedFile($value['tmp_name'], $field_name);
					}catch(FileNotFoundException $e){}
				}

			}
		}
	}

	/**
	 * Change default key of data. If we have array of
	 * data, we will append the index key and delimit it
	 * by dot(name.0)
	 * 
	 * @param string $field_name
	 * @param mixed $value
	 * 
	 * @param string
	 */
	private function setExplicitAttributes($field_name)
	{
		$field_name = $this->new_field_name ? $this->new_field_name . ".$field_name" : $field_name;
		return $field_name;
	}

	/**
	 * Main entry point of our validation
	 *
	 * @param array $data       The data to validate against the rules
	 * @param array $rules
	 * 
	 * @return $this
	 */
	public function make($data, $rules)
	{

		$this->parseData($data);
		
		foreach( $rules as $attribute => $rule ){
			
			$this->has_nullable = false;

			# If we found nullable rule in any position of the string
			# we will swap it to the begining of the pipes rules
			# so we can properly validate the this rule if included
			# in other rules(e.g nullable|min:2|max:30).
			$rule = $this->prependNullableRule($rule);
			
			// explode the rule
			foreach( (explode('|', $rule)) as $the_rule ){

				$this->validateAttributes( $attribute, $the_rule );
			}

			$this->complete(function($messageBag) use($attribute){

				if ( $this->has_nullable ) {
					if ( ! $this->validate_required($this->data, $attribute) ) {
						unset($messageBag[$attribute]);
					}
				}

				return $messageBag;
			 });
		}

		return $this;
	}

	/**
	 * Always prepend nullable rule at the begining of the pipes
	 * rules string
	 * 
	 * @param string $pipe_rules  The pipe(|) delimeted rules
	 * 
	 * @return string
	 */
	private function prependNullableRule( $pipe_rules )
	{
		$exploded = explode('|', $pipe_rules);


		# We if required is mixed with nullable
		if ( ($key = array_search('required', $exploded)) !== false ) {
			return $pipe_rules;
		}

		if ( ($key = array_search('nullable', $exploded)) !== false ) {

			$this->has_nullable = true;

			# Unset rule nullable
			unset($exploded[$key]);

			# Prepend nullable that the begining
			array_unshift($exploded, 'nullable');

			$pipe_rules = implode('|', $exploded);

		}

		return $pipe_rules;
	}

	/**
	 * Run validation
	 *
	 * @param string $attribute
	 * @param string $the_rule
	 * 
	 * @return $this
	 */
	private function validateAttributes( $attribute, $the_rule )
	{
		
		$the_rule = $this->extractRules($the_rule);

		$validator = "validate_$the_rule";
		
		$key = 0;

		# Unset all the keys with .* in our data
		list($var) = array_pad(explode('.', $attribute), 1, '');	
		unset($this->data["$var.*"]);
		if ( count($this->data) <= 0 ) {
			throw ValidatorException::no_data_to_validate();
		}
		foreach ( $this->data as $field => $value ) {

			if ( $this->str_contains($field, $var) ) {
				// extract explicit attributes
				$newAttribute = $this->extractExplicitAttributes($attribute, $key);
				call_user_func_array($this->run(), [$validator,$newAttribute, $the_rule]);
				$key++;
			} else {
				$this->skipAsteriskAttributes(
					$this->run(),
					$validator,$attribute, $the_rule
				);
			}
		}
		

		return $this;
	}

	/**
	 * Completing the validation
	 * 
	 * @param string $rule_name    The rule_name from message bag
	 * 
	 * @return void
	 */
	private function complete(Closure $callback)
	{
		
		$this->messages = call_user_func($callback, $this->messages);
		
	}

	/**
	 * Skip validating data if their keys
	 * contains .*
	 *
	 * @param \Closure $callback
	 * @param string $validator    The validate_name
	 * @param string $attribute
	 * @param string $the_rule
	 * 
	 * @return void
	 */
	private function skipAsteriskAttributes(\Closure $callback, $validator,$attribute, $the_rule)
	{
		
		if ( ! $this->str_contains($attribute, '*') ) {
			call_user_func_array($callback, [$validator,$attribute, $the_rule]);
		}
	}

	/**
	 * Finally, run the validation
	 *
	 * @param string $validator
	 * @param string $attribute
	 * @param string $the_rule
	 * 
	 * @return \Closure
	 */
	private function run()
	{
		return function($validator, $attribute, $the_rule){
			if ( method_exists( $this, $validator ) ) {
				
				if ( ! $this->{$validator}($this->data, $attribute, $this->extraRules) ) {
					$this->addFailure($attribute, $the_rule);
				}

			} else {
				throw ValidatorException::validator_not_found($the_rule);
			}
		};
	}

	/**
	 * Extract explicit attributes
	 * 
	 * @param string $attribute
	 * @param int $key      The key to be appended in attribute.{0} where 0 is the key
	 * 
	 * @return array
	 */
	private function extractExplicitAttributes($attribute, $key = null )
	{
		if ( $this->str_contains( $attribute, '.*' ) && !is_null($key) ) {
			return $attribute = (str_replace('*', $key, $attribute));
		}

		return $attribute;

	}

	/**
	 * @todo, move this to a helper later
	 * 
	 * @param string $haystack     The subject string being checked
	 * @param string|array $needles  The needle(s) to be search in the $haystack
	 * 
	 * @return bool
	 */
	private function str_contains($haystack, $needles)
	{
		
		foreach ( (array) $needles as $needle ) {
			if ( $needle != '' &&  mb_strpos($haystack, $needle) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract the rule, if rule format like mimes:jpg,png,jpg
	 * 
	 * @return string|array
	 */
	private function extractRules($rule)
	{
		// padd the array if the length is less than 2
		// and set padded value to null
		$rule = array_pad(explode(':', $rule), 2, null);

		list($rule,$extra) = $rule;
		// Store extra rules
		
		if ( $extra ) {
			$this->extraRules = str_getcsv($extra);
		}
		
		return $rule;
	}

	/**
	 * Check if validation fails
	 * 
	 * @return bool
	 */
	public function fails()
	{
		return count($this->messages) > 0;
	}

	/**
	 * Get the messages from validator
	 * 
	 * @return array
	 */
	public function getMessage()
	{
		return $this->messages;
	}


	/**
	 * Add failed message to message bag
	 *
	 * @param string $attribute
	 * @param string $rule
	 *
	 * @return bool
	 */
	private function addFailure( $attribute, $rule )
	{
		$replace = $this->translate(array_merge((array) $attribute, $this->extraRules));

		if ( !isset($this->messages[$attribute]) ) {
			$this->messages[$attribute] = str_replace( [':attribute', ':extras'], $replace, $this->default_messages[$rule] );
		}
	}

	/**
	 * Translator
	 * 
	 * @param  array  $array
	 * 
	 * @return array
	 */
	private function translate(array $array)
	{
		list($attribute) = $array;
		$extras = '';
		if ( count($array) > 1 ) {
			$extras = implode(',', array_splice($array, 1));
		}
		return [$attribute, $extras];
	}

	/**
	 * Validate required
	 * 
	 * @param  array $data
	 * @param  string $attribute The attribute name to validate against data
	 * 
	 * @return bool
	 */
	protected function validate_required( $data, $attribute )
	{
		$key = key($data);

		# If we are checking against an array of inputs
		# We will check if the array has atleast 1 value
		
		if ( $this->str_contains($key, '.') && isset($data[$key])) {
			return true;
		}
		return isset($data[$attribute]) && ! empty($data[$attribute]);
	}

	
	/**
	 * Validate required_with_all
	 * 
	 * @param  array $data
	 * @param  string $attribute The attribute name to validate against data
	 * @param  array $parameters The array of extra parameters
	 * 
	 * @return bool
	 */
	protected function validate_required_with_all( $data, $attribute, array $parameters = array() )
	{

		$errors = [];
		foreach($parameters as $parameter){

			if ( array_key_exists($parameter, $data) ) {
				$errors[] = $parameter;
			}
			$parameter = null;
		}
		
		if ( count($errors) >= count($parameters) ) {

			return $this->validate_required($data, $attribute);
		}

		return true;
	}

	/**
	 * Validate email
	 * 
	 * @param  array $data
	 * @param  string $attribute The attribute name to validate against data
	 * 
	 * @return bool
	 */
	protected function validate_email( $data, $attribute )
	{
		return isset($data[$attribute]) && filter_var( $data[$attribute], FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Validate integer
	 *
	 * @param array $data
	 * @param string $attribute The attribute name to validate against data
	 *
	 * @return bool
	 */
	protected function validate_integer( $data, $attribute )
	{
		return isset($data[$attribute]) && is_int($data[$attribute]);
	}

	/**
	 * Validate number
	 *
	 * @param array $data
	 * @param string $attribute
	 *
	 * @return bool
	 */
	public function validate_number( $data, $attribute )
	{
		return isset($data[$attribute]) && is_numeric($data[$attribute]);
	}


	/**
	 * Confirmation validation
	 *
	 * The vfield under validation must have a matching fielf of `foo_confirmation`.
	 * For example, if the field under validation is `password`, a matching `password_confirmation`
	 * field must be present in the input.
	 *
	 * @param array $data
	 * @param string $attibute    The attribute name to validate against data
	 *
	 * @return bool
	 */
	protected function validate_confirmed( $data, $attribute )
	{

		if ( !isset($data[$attribute . '_confirmation']) ) {
			return false;
		}

		return $data[$attribute] === $data[$attribute . '_confirmation'];

	}

	/**
	 * Validate min
	 *
	 * @param array $data
	 * @param string $attribute
	 * @param array $parameters   The array of extra parameters. e.g min:3. 3 is the extra here
	 * 
	 * @return bool
	 */
	protected function validate_min( $data, $attribute, $parameters = array() )
	{

		if ( $this->validate_string( $data, $attribute ) && $this->getStringLength($data[$attribute]) >= $parameters[0] ) {
			return true;
		}

		return $this->validate_integer( $data, $attribute )  && $data[$attribute] >= $parameters[0];

	}

	/**
     * Password must contain uppercase, lowercase, number, special characters and at least 8 characters
     *
     *
     * @param  string $attribute       The input attribute name
     * @param  mixed $value            The input's value
     * @param  array $parameter        The array of rule parameter
     *
     * @return  void
     */
    protected function validate_strong_password($data, $attribute, $parameter = array())
    {

        $uppercase = preg_match('@[A-Z]@', $data[$attribute]);
        $lowercase = preg_match('@[a-z]@', $data[$attribute]);
        $number = preg_match('@[0-9]@', $data[$attribute]);
        $special_chars = preg_match('@[\W]@', $data[$attribute]);

        $length = count($parameter) > 0 ? (int) $parameter[0] : 8;

        $valid = ($uppercase && $lowercase && $number && $special_chars && strlen($data[$attribute]) >= $length);

        if (!$valid) {
            return false;
        }

        return true;
    }

	/**
	 * Validate max
	 * 
	 * @param array $data
	 * @param string $attribute
	 * @param array $parameters array $parameters   The array of extra parameters. e.g max:3. 3 is the extra here
	 * 
	 * @return bool
	 */
	public function validate_max( $data, $attribute, $parameters = array() )
	{
		if ( $this->validate_string( $data, $attribute ) && $this->getStringLength($data[$attribute]) <= $parameters[0] ) {
			return true;
		}

		return $this->validate_integer( $data, $attribute )  && $data[$attribute] <= $parameters[0];
	}


	/**
	 * Get the length of the string
	 * 
	 * @param  string $str
	 * 
	 * @return int
	 */
	private function getStringLength($str)
	{
		return strlen($str);
	}

	/**
	 * Validate string
	 *
	 * @param array $data
	 * @param string $attribute
	 * 
	 * @return bool
	 */
	protected function validate_string( $data, $attribute )
	{
		return isset($data[$attribute]) && is_string($data[$attribute]);
	}

	/**
	 * Validate nullable
	 *
	 * @return bool
	 */
	protected function validate_nullable( $data, $attribute )
	{
		//unset($this->messages[$attribute]);
		return true;
	}

	/**
	 * Validate mime type
	 * 
	 * @param  array $data
	 * @param  strin $attribute
	 * @param  array  $parameters  array of extra parameters to be validated
	 * 
	 * @return bool
	 */
	protected function validate_mimes( $data, $attribute, $parameters = array() )
	{
		
		// Nothing to validate
		if ( !isset($data[$attribute]) ) {
			return true;
		}

		$file = $data[$attribute];
		
		if ( $file instanceof UploadedFile ) {
			return $file->getPath() !== '' && in_array($file->guessExtension(), $parameters);
		}

		return false;
	}


	/**
	 * Validate image
	 *
	 *  
	 */
	protected function validate_image( $data, $attribute )
	{	
		return $this->validate_mimes($data, $attribute, ['jpeg', 'png', 'gif', 'bmp', 'svg']);
	}
	

	/**
	 * Validate url
	 * 
	 * @param  array $data
	 * @param  string $attribute The attribute name to validate against data
	 * 
	 * @return bool
	 */
	protected function validate_url( $data, $attribute )
	{
		return isset($data[$attribute]) && ! empty($data[$attribute]) && (filter_var($data[$attribute], FILTER_VALIDATE_URL));
	}

	/**
	 * Remove data from $_FILES with size = 0
	 *
	 * @return array $_FILES
	 */
	public function removeEmptyFiles()
	{
		if ( isset($_FILES) ) {
			foreach ( $_FILES as $key => $files ) {
				foreach ( (array) $files['size'] as $size ) {
					if ( $size <= 0 ) {
						unset($_FILES[$key]);
					}
				}
			}
			return $_FILES;
		}

		return [];
	}
}