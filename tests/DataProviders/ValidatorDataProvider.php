<?php

namespace Tests\DataProviders;

use stdClass;

class ValidatorDataProvider
{


	public function requiredValidation()
	{
		$_POST = [
			'firstname' => '',
		];

		return [
			array($_POST)
		];
	}

	public function emailValidation()
	{
		$_POST = [
			'firstname' => 'john',
			'email'     => 'invalidemail'
		];

		return [
			array($_POST)
		];
	}

	public function passedValidation()
	{
		$_POST = [
			'firstname' => 'john',
			'lastname'  => 'doe',
			'email'     => 'john.doe@example.com'
		];

		return [
			array($_POST)
		];
	}

	public function integer()
	{
		$_POST = [
			'amount' => 1000,
		];

		return [
			array($_POST)
		];
	}

	public function dataArray()
	{
		$_POST = [
			'images' => ['image1', 'image2'],
		];

		return [
			array($_POST)
		];
	}

	public function confirm_password()
	{
		$_POST = [
			'password' => 'PASSWORD',
			'password_confirmation' => 'PASSWORD'
		];

		return [
			array($_POST)
		];
	}

	/**
	 * Data structure for array of fields
	 */
	public function array_fields()
	{
		/**
		 * Example in form <input type="text" name="name[]" value="">
		 */
		$_POST = [
			array(
				'name' => 'Anderson'
			),
			array(
				'name' => 'ddfd'
			),
			'email' => 'valid@email.com',
		];

		return [
			array($_POST)
		];
	}

}