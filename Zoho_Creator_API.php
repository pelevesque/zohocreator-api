<?php
/**
 * A PHP class to consume Zoho Creator's API.
 *
 * This API consumes four methods from the Zoho Creator API:
 * - create ticket
 * - destroy ticket
 * - add
 * - update
 *
 * Please read the parse_response() method to understand the output
 * provided for each of the method calls.
 *
 * On line 287, we use a hack to find out if the update method
 * failed due to no matching records being found in Zoho's database.
 * Unfortunately, Zoho's API does not have a search method.
 * If Zoho changes the error message provided when the update
 * call fails due to this error, this code will have to be changed accordingly.
 *
 * @version     1.0
 * @for         https://www.zoho.com/creator/
 * @author      Pierre-Emmanuel Lévesque
 * @email       pierre.e.levesque@gmail.com
 * @copyright   Copyright 2011, Pierre-Emmanuel Lévesque
 * @license     MIT License - @see README.md
 */
class Zoho_Creator_API {

	/**
	 * Zoho  username or email
	 * @var  string
	 */
	protected $_login_id;

	/**
	 * Zoho  password
	 * @var  string
	 */
	protected $_password;

	/**
	 * Zoho  API key
	 * @var  string
	 */
	protected $_api_key;

	/**
	 * Zoho  API ticket
	 * @var  string
	 */
	protected $_api_ticket = NULL;

	/**
	 * Zoho  API URL
	 * @var  string
	 */
	protected $_api_url = 'https://creator.zoho.com/api/';

	/**
	 * Zoho  application
	 * @var  string
	 */
	public $application;

	/**
	 * Zoho error descriptions
	 *
	 * @see:  https://api.creator.zoho.com/Creator-Error-Codes.html
	 * @note: For some reason, error descriptions are not provided in Zoho's response.
	 * @var   array
	 */
	public $errors = array(
		'2830' => 'Invalid XML.',
		'2831' => 'Missing apikey in the request.',
		'2832' => 'Missing application owner in the request.',
		'2833' => 'Missing application name in the request.',
		'2834' => 'Missing form name in the request.',
		'2835' => 'Missing view name in the request.',
		'2836' => 'Missing operation in the request.',
		'2890' => 'Invalid apikey.',
		'2891' => 'Invalid application owner.',
		'2892' => 'Invalid application name.',
		'2893' => 'Invalid form name.',
		'2894' => 'Invalid view name.',
		'2895' => 'Invalid operation.',
		'2896' => 'Permission denied to delete records.',
		'2897' => 'Permission denied to update records.',
		'2898' => 'Permission denied to view records.',
		'2899' => 'Permission denied to add records.',
		'2900' => 'Invalid column name.',
		'2901' => 'Invalid Operator.',
		'2902' => 'No records found with specified criteria.',
		'2903' => 'Incomplete criteria. The final relational operator must end with a dot(.)',
		'2904' => 'Value specified for formula field.',
		'2905' => 'Error occured while fetching data. Your data is safe.',
		'2906' => 'NULL criteria provided for delete.',
		'2907' => 'NULL criteria provided for update.',
		'2907' => 'NULL criteria provided for view.',
		'2909' => 'Get request not supported.',
		'2910' => 'Invalid Email-id.',
		'2911' => 'No access.',
		'2912' => 'No such user.',
		'2913' => 'Invalid ticket.',
		'2914' => 'Private and shared applications cannot be copied.',
		'2915' => 'Limit should not exceed 5000.',
		'2917' => 'You must login to access this API.',
	);

	/**
	 * Constructor
	 *
	 * @param   string  login_id
	 * @param   string  password
	 * @param   string  API key
	 * @param   string  application
	 * @return  none
	 */
	public function __construct($_login_id, $_password, $_api_key, $application = NULL)
	{
		$this->_login_id = $_login_id;
		$this->_password = $_password;
		$this->_api_key = $_api_key;
		$this->application = $application;
	}

	/**
	 * Initializes the API ticket
	 *
	 * @param   none
	 * @return  array   parsed response
	 */
	public function init_api_ticket()
	{
		// Set the data.
		$data['LOGIN_ID'] = $this->_login_id;
		$data['PASSWORD'] = $this->_password;
		$data['FROM_AGENT'] = 'true';
		$data['servicename'] = 'ZohoCreator';

		// Set the request URL.
		$url = 'https://accounts.zoho.com/login?'.http_build_query($data);

		// Execute the request and parse the response.
		$response = $this->request($url);
		$response = $this->parse_response($response, 'api_ticket');

		// Set the API ticket.
		$this->_api_ticket = ($response['success'] === TRUE) ? $response['api_ticket'] : NULL;

		return $response;
	}

	/**
	 * Kills the API ticket
	 *
	 * @param   none
	 * @return  array   parsed response
	 */
	public function kill_api_ticket()
	{
		// Set the data.
		$data['ticket'] = $this->_api_ticket;
		$data['FROM_AGENT'] = 'true';

		// Set the request URL.
		$url = 'https://accounts.zoho.com/logout?'.http_build_query($data);

		// Execute the request and parse the response.
		$response = $this->request($url);
		$response = $this->parse_response($response, 'api_ticket');

		// Unset the API ticket.
		($response['success'] === TRUE) AND $this->_api_ticket = NULL;

		return $response;
	}

	/**
	 * Adds an entry
	 *
	 * @param   string  form
	 * @param   array   data
	 * @return  array   parsed response
	 */
	public function add($form, $data)
	{
		// Add required data.
		$data['apikey'] = $this->_api_key;
		$data['ticket'] = $this->_api_ticket;

		// Set the request URL.
		$url = $this->_api_url.'xml/'.$this->application.'/'.$form.'/add/';

		// Execute the request and parse the response.
		$response = $this->request($url, $data);
		$response =	simplexml_load_string($response);
		$response = $this->parse_response($response, 'add');

		return $response;
	}

	/**
	 * Updates an entry
	 *
	 * @param   string  form
	 * @param   array   data
	 * @param   string  criteria
	 * @param   string  reloperator (AND,OR)
	 * @return  array   parsed response
	 */
	public function update($form, $data, $criteria, $reloperator = 'AND')
	{
		// Add required data.
		$data['apikey'] = $this->_api_key;
		$data['ticket'] = $this->_api_ticket;
		$data['criteria'] = $criteria;
		$data['reloperator'] = $reloperator;

		// Set the request URL.
		$url = $this->_api_url.'xml/'.$this->application.'/'.$form.'/update/';

		// Execute the request and parse the response.
		$response = $this->request($url, $data);
		$response = simplexml_load_string($response);
		$response = $this->parse_response($response, 'update');

		return $response;
	}

	/**
	 * Tries to update else add an entry
	 *
	 * @param   string  form
	 * @param   array   data
	 * @param   string  criteria
	 * @return  array   parsed response
	 */
	public function update_else_add($form, $data, $criteria)
	{
		// Try updating.
		$response = $this->update($form, $data, $criteria);
		$response['method'] = 'update';

		// Update success.
		if ($response['success'])
		{
			// Could not update. (Failure, No Records Found With Specified Criteria)
			if ( ! $response['updated'])
			{
				// Try adding.
				$response = $this->add($form, $data);
				$response['method'] = 'add';
			}
		}

		return $response;
	}

	/**
	 * Executes a request using Curl
	 *
	 * @param   string  url
	 * @param   array   data
	 * @return  mixed   response
	 */
	protected function request($url, $data = array())
	{
		// Initialize Curl.
		$client = curl_init($url);

		// Set options.
		curl_setopt($client, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($client, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($client, CURLOPT_POST, TRUE);
		! empty($data) AND curl_setopt($client, CURLOPT_POSTFIELDS, $data);

		// Execute the request.
		$response = curl_exec($client);

		// Close connection.
		curl_close($client);

		return $response;
	}

	/**
	 * Parses the response
	 *
	 * @param   mixed   response
	 * @param   string  type
	 * @return  array   parsed response
	 */
	protected function parse_response($response, $type)
	{
		// Set the original response.
		$output['response'] = $response;

		// Good request.
		if ($response)
		{
			// Add or update.
			if ($type == 'add' OR $type =='update')
			{
				// Includes status.
				if (isset($response->result->form->$type->status))
				{
					// Success.
					if ($response->result->form->$type->status == 'Success')
					{
						$output['success'] = TRUE;
						$type == 'update' AND $output['updated'] = TRUE;
					}
					// Error.
					else
					{
						$message = (array) $response->result->form->$type->status;
						$message = $message[0];
						$code = ($message == 'Failure, No Records Found With Specified Criteria') ? '2902' : NULL;
						($code == '2902') AND $message = $this->errors[$code];
					}
				}
				// Includes errorlist with code.
				else if (isset($response->errorlist->error->code))
				{
					$code = (array) $response->errorlist->error->code;
					$code = $code[0];
					$message = ($code != NULL AND isset($this->errors[$code])) ? $this->errors[$code] : NULL;
				}

				// Finish processing requests without status == 'Success'.
				if ( ! isset($output['success']))
				{
					if ($type == 'update' AND isset($code) AND $code == '2902')
					{
						$output['success'] = TRUE;
						$output['updated'] = FALSE;
					}
					else
					{
						$output['success'] = FALSE;
						$output['error']['code'] = isset($code) ? $code : NULL;
						$output['error']['message'] = isset($message) ? $message : NULL;
					}
				}
			}
			// API ticket init and kill.
			elseif ($type == 'api_ticket')
			{
				// Extract params.
				$params = array();
				$parts = explode("\n", $response);
				foreach ($parts as $part)
				{
					$part = explode('=', $part);
					$params[$part[0]] = $part[1];
				}

				// Success.
				if ($params['RESULT'] == 'TRUE')
				{
					$output['success'] = TRUE;
					isset($params['TICKET']) AND $output['api_ticket'] = $params['TICKET'];
				}
				// Error.
				else
				{
					$output['success'] = FALSE;
					$output['error']['code'] = NULL;
					if (isset($params['CAUSE']) AND $params['CAUSE'] != 'null')
					{
						$output['error']['message'] = $params['CAUSE'];
					}
					elseif (isset($params['WARNING']) AND $params['WARNING'] != 'null')
					{
						$output['error']['message'] = $params['WARNING'];
					}
					else
					{
						$output['error']['message'] = NULL;
					}
				}
			}
		}
		// Bad request.
		else
		{
			$output['success'] = FALSE;
			$output['error']['code'] = '400';
			$output['error']['message'] = 'Bad Request.';
		}

		return $output;
	}

} // END Zoho_Creator_API
