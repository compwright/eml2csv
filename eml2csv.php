<?php

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/vendor/autoload.php';

array_shift($argv);

$files = $argv;
if (empty($files))
{
	fputs(STDERR, 'No files' . PHP_EOL);
	exit(1);
}

fputcsv(STDOUT, [
	'email',
	'firstName',
	'lastName',
	'suffix',
	'address',
	'address2',
	'city',
	'state',
	'zipcode',
	'phone',
]);

foreach ($files as $file)
{
	list($email, $name, $subject, $body) = parseMessage($file);
	$name = $name ?: extractName($subject);
	list($firstName, $lastName, $suffix) = splitName($name);
	list($address, $address2, $city, $state, $zipcode, $phone) = extractAddress($body, strpos($body, "\n\n"));

	fputcsv(STDOUT, [
		$email,
		$firstName,
		$lastName,
		$suffix,
		$address,
		$address2,
		$city,
		$state,
		$zipcode,
		$phone,
	]);
}

function parseMessage($file)
{
	$set = new ezcMailFileSet([ $file ]);
	$parser = new ezcMailParser();
	$mail = $parser->parseMail($set);
	$mail = $mail[0];

	$body = null;
	if (!empty($mail->body))
	{
		if ($mail->body instanceof ezcMailMultiPart)
		{
			$parts = $mail->body->getParts();
			foreach ($parts as $part)
			{
				if ($part instanceof ezcMailText) {
					$body = $part->text;
					break;
				}
			}
		}
		else
		{
			$body = $mail->body;

			if ($body instanceof ezcMailText)
			{
				$body = $body->text;
			}
		}
	}

	return [
		(string) $mail->from->email,
		(string) $mail->from->name,
		(string) $mail->subject,
		(string) $body,
	];
}

function extractName($subject)
{
	$regexes = [
		'/from ([\w\s.]+) to/',
		'/from ([\w\s.]+)$/',
	];

	foreach ($regexes as $regex)
	{
		if (preg_match($regex, $subject, $matches))
		{
			return $matches[1];
		}
	}

	return '';
}

function splitName($name)
{
	$parts = explode(' ', trim($name));

	$lastName = array_pop($parts);
	if (strpos($lastName, '.'))
	{
		$suffix = $lastName;
		$lastName = array_pop($parts);
	}
	else
	{
		$suffix = '';
	}

	$firstName = trim(implode(' ', $parts));

	return [
		$firstName,
		$lastName,
		$suffix,
	];
}

function extractAddress($body, $startAt)
{
	$regex = '/^(.*)\n(.*)?\n?(.*)\n([\w\s.]+), ([\w]{2}) ([\d]{5})(-[\d]{4})?\n?([\d\(\)\s-]*)?$/';
	$body = trim(substr($body, $startAt));
	if (preg_match($regex, $body, $matches))
	{
		return [
			$matches[2], // address
			$matches[3], // address2
			$matches[4], // city
			$matches[5], // state
			$matches[6].$matches[7], // zip(-zip4)
			$matches[8], // phone
		];
	}
	else
	{
		return [];
	}
}

exit(0);
