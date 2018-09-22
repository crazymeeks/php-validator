<?php

namespace Tests\Validation;

use Tests\TestCase;
use Crazymeeks\Validation\Validator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidatorTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();
	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::requiredValidation()
	 */
	public function it_should_return_true_if_required_validation_fails($dataArray)
	{

		$validation = $this->validator->make($dataArray, [
			'firstname' => 'required',
			'lastname'  => 'required',
		]);
		
		$this->assertTrue($validation->fails());

	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::emailValidation()
	 */
	public function it_should_fail_if_email_is_invalid($dataArray)
	{

		$validation = $this->validator->make($dataArray, [
			'firstname' => 'required',
			'email'  => 'required|email',
		]);
		
		$this->assertTrue($validation->fails());

	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::passedValidation()
	 */
	public function it_should_return_false_if_validation_passed($dataArray)
	{

		$validation = $this->validator->make($dataArray, [
			'firstname' => 'required',
			'lastname' => 'required',
			'email'  => 'required|email',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::integer
	 */
	public function it_should_validate_integer($dataArray)
	{

		$validation = $this->validator->make($dataArray, [
			'amount' => 'integer',
		]);

		$this->assertFalse($validation->fails());
	}


	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::array_fields()
	 */
	public function it_should_validate_array_fields($fields)
	{
		$_POST = $fields;

		
		$this->validator->make($_POST, [
			'name.*' => 'required',
			'email' => 'required|email'
		]);
		
		$this->assertFalse($this->validator->fails());
	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::confirm_password()
	 */
	public function it_should_validate_password_confirmation($data)
	{
		
		$validation = $this->validator->make($data, [
			'password' => 'confirmed',
		]);

		$this->assertFalse($validation->fails());
	}
	

	/**
	 * @test
	 */
	public function it_should_validate_mime_types()
	{

		$_FILES = [
			'uploaded_file' => [
				'name' => 'car.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/_files/car.jpg',
				'error' => 0,
				'size' => 200000,
			],
		];

		
		$validation = $this->validator->make($_FILES, [
			'uploaded_file' => 'mimes:png,jpg,jpeg',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_image()
	{
		$_POST = ['name' => 'John'];
		$_FILES = [
			'uploaded_file' => [
				'name' => 'car.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/_files/car.jpg',
				'error' => 0,
				'size' => 200000,
			],
			'anotherfile' => [
				'name' => '',
				'type' => '',
				'tmp_name' =>'',
				'error' => 4,
				'size' => 0,
			],
		];
		
		
		$validation = $this->validator->make($_FILES, [
			'uploaded_file' => 'image',
			'anotherfile' => 'required',
		]);
		
		$this->assertTrue($validation->fails());

	}

	/**
	 * @test
	 */
	public function it_should_remove_empty_files()
	{
		$_FILES['webimage'] = [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => 4,
				'size' => 0,
		];
		$_FILES['mobileimage'] = [
			'name' => '',
			'type' => '',
			'tmp_name' => '',
			'error' => 4,
			'size' => 0,
		];
		$result = $this->validator->removeEmptyFiles();
		$this->assertTrue((count($result)== 0));
	}

	/**
	 * @test
	 * @dataProvider Tests\DataProviders\ValidatorDataProvider::array_fields()
	 */
	public function it_should_validate_array_of_images($data)
	{
		$_FILES = [
			'uploaded_file' => [
				'name' => [
					'car.jpg',
					'car-normal.jpg'
				],
				'type' => [
					'image/jpg',
					'image/jpg',
				],
				'tmp_name' => [
					__DIR__ . '/_files/car.jpg',
					__DIR__ . '/_files/car-normal.jpg',
				],
				'error' => [
					0,
					0
				],
				'size' => [
					200000,
					1800,
				],
			],
		];

		$_POST = $data;
		
		$validation = $this->validator->make($_FILES, [
			'uploaded_file.*' => 'image',
		]);
		
		$this->assertFalse($validation->fails());

	}

	/**
	 * @test
	 */
	public function it_should_validate_int_min()
	{
		$_POST = [
			'age' => 3,
		];

		
		$validation = $this->validator->make($_POST, [
			'age' => 'min:3',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_min_string()
	{
		$_POST = [
			'year' => '2001',
		];

		
		$validation = $this->validator->make($_POST, [
			'year' => 'min:4',
		]);
		
		$this->assertFalse($validation->fails());
	}


	/**
	 * @test
	 */
	public function it_should_validate_string()
	{
		$_POST = [
			'year' => '2001',
		];

		
		$validation = $this->validator->make($_POST, [
			'year' => 'string',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_max()
	{
		$_POST = [
			'age' => 50,
		];

		
		$validation = $this->validator->make($_POST, [
			'age' => 'max:50',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_max_string()
	{
		$_POST = [
			'mystring' => 'countme',
		];

		
		$validation = $this->validator->make($_POST, [
			'mystring' => 'max:7',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_number()
	{
		$_POST = [
			'zipcode' => 89483943,
		];

		
		$validation = $this->validator->make($_POST, [
			'zipcode' => 'number',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_nullable()
	{
		$_POST = [
			'zipcode' => '',
		];

		$validation = $this->validator->make($_POST, [
			'zipcode' => 'min:10|nullable|max:20',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_nullable_with_other_validation()
	{
		$_POST = [
			'email' => '',
		];

		
		$validation = $this->validator->make($_POST, [
			'email' => 'email|nullable',
		]);
		
		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function validate_required__should_return_true_if_array_of_inputs_is_not_empty()
	{
		$_POST = [
			'name' => [
				'dfd',
				''
			]
		];

		$validation = $this->validator->make($_POST, [
			'name' => 'required',
		]);
		$this->assertFalse($validation->fails());
	}

	/**
	 * The field under validation must be present and not empty only if all of the other specified fields are present.
	 * required_with_all:foo,bar,...
	 * 
	 * @test
	 */
	public function it_should_validate_required_with_all()
	{
		$_POST = [
			'field1' => '2018-10-09',
			'field2' => '2021-10-09',
			'checkbox1' => 'on',
			'checkbox2' => 'on',
		];

		
		$validation = $this->validator->make($_POST, [
			'field1' => 'required_with_all:checkbox1,checkbox2',
			'field2' => 'required_with_all:checkbox1,checkbox2',
		]);

		$this->assertFalse($validation->fails());
	}

	/**
	 * @test
	 */
	public function it_should_validate_strong_password()
	{
		$_POST = [
			'password' => '8l(F8394850'
		];

		
		$validation = $this->validator->make($_POST, [
			'password' => 'strong_password',
		]);

		$this->assertFalse($validation->fails());	

	}
	
	/**
	 * @test
	 */
	public function it_should_validate_url()
	{
		$_POST = [
			'website' => 'http://google.com'
		];

		$validation = $this->validator->make($_POST, [
			'website' => 'url',
		]);

		$this->assertFalse($validation->fails());

	}

}