<?php

namespace App\Http\Controllers\Api;

use Log;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use TextMagic\Models\SendMessageRequest;
use TextMagic\Api\TextMagicApi;
use TextMagic\Configuration;
use GuzzleHttp;

class TextMagicController extends Controller
{

	/**
	 * Send SMS text to phone numbers
	 */
	public function sendSMStoClient($content, $phones){

		if (!empty($content) and is_array($phones) and count($phones) > 0){

			$config = Configuration::getDefaultConfiguration()
				->setUsername(env('TEXTMAGIC_API_USER'))
				->setPassword(env('TEXTMAGIC_API_KEY'));

			$api = new TextMagicApi(
				new GuzzleHttp\Client(),
				$config
			);

			// Send a new message request
			$input = new SendMessageRequest();
			$input->setText($content);
			$input->setPhones(implode(',',$phones));

			try {
				$result = $api->sendMessage($input);
				Log::channel('textmagicsms')->info('TO '.implode(',',$phones).':', (array)$result);
			} catch (Exception $e) {
				echo 'Exception when calling TextMagicApi->sendMessage: ', $e->getMessage(), PHP_EOL;
			}


		}

	}

    /**
     * Handle incoming callbacks.
     */
    public function handle(Request $request)
    {
        // 1) Inspect incoming payload
        $data = $request->all();

        // 2) Do whatever processing you need...
        Log::channel('textmagicsms')->info('Callback received', $data);

        // 3) Return a JSON “200 OK” response:
        return response()->json([
            'status'  => 'success',
            'message' => 'Callback processed',
        ], 200);
    }

}
