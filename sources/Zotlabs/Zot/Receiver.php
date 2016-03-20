<?php

namespace Zotlabs\Zot;

class Receiver {

	protected $data;
	protected $encrypted;
	protected $error;
	protected $messagetype;
	protected $sender;
	protected $validated;
	protected $recipients;
	protected $response;
	protected $handler;

	function __construct($data,$prvkey,$handler) {

		$this->error       = false;
		$this->validated   = false;
		$this->messagetype = '';
		$this->response    = array('success' => false);

		$this->handler = $handler;

		if(! is_array($data))
			$data = json_decode($data,true);

		if($data && is_array($data)) {
			$this->encrypted = ((array_key_exists('iv',$data)) ? true : false);

			if($this->encrypted) {
				$this->data = @json_decode(@crypto_unencapsulate($data,$prvkey),true);
			}
			if(! $this->data)
				$this->data = $data;

			if($this->data && is_array($this->data) && array_key_exists('type',$this->data))
				$this->messagetype = $this->data['type'];
		}
		if(! $this->messagetype)
			$this->error = true;

		if($this->data) {
			$this->sender     = ((array_key_exists('sender',$this->data)) ? $this->data['sender'] : null);
			$this->recipients = ((array_key_exists('recipients',$this->data)) ? $this->data['recipients'] : null);
		}

		if($this->sender)
			$this->ValidateSender();

		$this->Dispatch();
	}

	function ValidateSender() {
		$hubs = zot_gethub($this->sender,true);
		if (! $hubs) {

			/* Have never seen this guid or this guid coming from this location. Check it and register it. */
			/* (!!) this will validate the sender. */

        	$result = zot_register_hub($this->sender);

        	if ((! $result['success']) || (! ($hubs = zot_gethub($this->sender,true)))) {
            	$this->response['message'] = 'Hub not available.';
	            json_return_and_die($this->response);
    	    }
		}
		foreach($hubs as $hub) {
			update_hub_connected($hub,((array_key_exists('sitekey',$this->sender)) ? $this->sender['sitekey'] : ''));
		}
		$this->validated = true;
    }

		
	function Dispatch() {

		/* Handle tasks which don't require sender validation */

		switch($this->messagetype) {
			case 'ping':
				/* no validation needed */
				$this->handler->Ping();
				break;
			case 'pickup':
				/* perform site validation, as opposed to sender validation */
				$this->handler->Pickup($this->data);
				break;

			default:
				if(! $this->validated) {
					$this->response['message'] = 'Sender not valid';
					json_return_and_die($this->response);
				}
				break;
		}

		/* Now handle tasks which require sender validation */

		switch($this->messagetype) {

			case 'auth_check':
				$this->handler->AuthCheck($this->data,$this->encrypted);
				break;

			case 'request':
				$this->handler->Request($this->data);
				break;

			case 'purge':
				$this->handler->Purge($this->sender,$this->recipients);
				break;

			case 'refresh':
			case 'force_refresh':
				$this->handler->Refresh($this->sender,$this->recipients);
				break;

			case 'notify':
				$this->handler->Notify($this->data);
				break;

			default:
				$this->response['message'] = 'Not implemented';
				json_return_and_die($this->response);
				break;
		}

	}
}



/**
 * @brief zot communications and messaging.
 *
 * Sender HTTP posts to this endpoint ($site/post typically) with 'data' parameter set to json zot message packet.
 * This packet is optionally encrypted, which we will discover if the json has an 'iv' element.
 * $contents => array( 'alg' => 'aes256cbc', 'iv' => initialisation vector, 'key' => decryption key, 'data' => encrypted data);
 * $contents->iv and $contents->key are random strings encrypted with this site's RSA public key and then base64url encoded.
 * Currently only 'aes256cbc' is used, but this is extensible should that algorithm prove inadequate.
 *
 * Once decrypted, one will find the normal json_encoded zot message packet.
 * 
 * Defined packet types are: notify, purge, refresh, force_refresh, auth_check, ping, and pickup 
 *
 * Standard packet: (used by notify, purge, refresh, force_refresh, and auth_check)
 * \code{.json}
 * {
 *   "type": "notify",
 *   "sender":{
 *     "guid":"kgVFf_1...",
 *     "guid_sig":"PT9-TApzp...",
 *     "url":"http:\/\/podunk.edu",
 *     "url_sig":"T8Bp7j5...",
 *   },
 *   "recipients": { optional recipient array },
 *   "callback":"\/post",
 *   "version":1,
 *   "secret":"1eaa...",
 *   "secret_sig": "df89025470fac8..."
 * }
 * \endcode
 *
 * Signature fields are all signed with the sender channel private key and base64url encoded.
 * Recipients are arrays of guid and guid_sig, which were previously signed with the recipients private 
 * key and base64url encoded and later obtained via channel discovery. Absence of recipients indicates
 * a public message or visible to all potential listeners on this site.
 *
 * "pickup" packet:
 * The pickup packet is sent in response to a notify packet from another site
 * \code{.json}
 * {
 *   "type":"pickup",
 *   "url":"http:\/\/example.com",
 *   "callback":"http:\/\/example.com\/post",
 *   "callback_sig":"teE1_fLI...",
 *   "secret":"1eaa...",
 *   "secret_sig":"O7nB4_..."
 * }
 * \endcode
 *
 * In the pickup packet, the sig fields correspond to the respective data
 * element signed with this site's system private key and then base64url encoded.
 * The "secret" is the same as the original secret from the notify packet. 
 *
 * If verification is successful, a json structure is returned containing a
 * success indicator and an array of type 'pickup'.
 * Each pickup element contains the original notify request and a message field
 * whose contents are dependent on the message type.
 *
 * This JSON array is AES encapsulated using the site public key of the site
 * that sent the initial zot pickup packet.
 * Using the above example, this would be example.com.
 *
 * \code{.json}
 * {
 *   "success":1,
 *   "pickup":{
 *     "notify":{
 *       "type":"notify",
 *       "sender":{
 *         "guid":"kgVFf_...",
 *         "guid_sig":"PT9-TApz...",
 *         "url":"http:\/\/z.podunk.edu",
 *         "url_sig":"T8Bp7j5D..."
 *       },
 *       "callback":"\/post",
 *       "version":1,
 *       "secret":"1eaa661..."
 *     },
 *     "message":{
 *       "type":"activity",
 *       "message_id":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *       "message_top":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *       "message_parent":"10b049ce384cbb2da9467319bc98169ab36290b8bbb403aa0c0accd9cb072e76@podunk.edu",
 *       "created":"2012-11-20 04:04:16",
 *       "edited":"2012-11-20 04:04:16",
 *       "title":"",
 *       "body":"Hi Nickordo",
 *       "app":"",
 *       "verb":"post",
 *       "object_type":"",
 *       "target_type":"",
 *       "permalink":"",
 *       "location":"",
 *       "longlat":"",
 *       "owner":{
 *         "name":"Indigo",
 *         "address":"indigo@podunk.edu",
 *         "url":"http:\/\/podunk.edu",
 *         "photo":{
 *           "mimetype":"image\/jpeg",
 *           "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
 *         },
 *         "guid":"kgVFf_...",
 *         "guid_sig":"PT9-TAp...",
 *       },
 *       "author":{
 *         "name":"Indigo",
 *         "address":"indigo@podunk.edu",
 *         "url":"http:\/\/podunk.edu",
 *         "photo":{
 *           "mimetype":"image\/jpeg",
 *           "src":"http:\/\/podunk.edu\/photo\/profile\/m\/5"
 *         },
 *         "guid":"kgVFf_...",
 *         "guid_sig":"PT9-TAp..."
 *       }
 *     }
 *   }
 * }
 * \endcode
 *
 * Currently defined message types are 'activity', 'mail', 'profile', 'location'
 * and 'channel_sync', which each have different content schemas.
 *
 * Ping packet:
 * A ping packet does not require any parameters except the type. It may or may
 * not be encrypted.
 *
 * \code{.json}
 * {
 *   "type": "ping"
 * }
 * \endcode
 *
 * On receipt of a ping packet a ping response will be returned:
 *
 * \code{.json}
 * {
 *   "success" : 1,
 *   "site" {
 *     "url": "http:\/\/podunk.edu",
 *     "url_sig": "T8Bp7j5...",
 *     "sitekey": "-----BEGIN PUBLIC KEY-----
 *                 MIICIjANBgkqhkiG9w0BAQE..."
 *   }
 * }
 * \endcode
 *
 * The ping packet can be used to verify that a site has not been re-installed, and to 
 * initiate corrective action if it has. The url_sig is signed with the site private key
 * and base64url encoded - and this should verify with the enclosed sitekey. Failure to
 * verify indicates the site is corrupt or otherwise unable to communicate using zot.
 * This return packet is not otherwise verified, so should be compared with other
 * results obtained from this site which were verified prior to taking action. For instance
 * if you have one verified result with this signature and key, and other records for this 
 * url which have different signatures and keys, it indicates that the site was re-installed
 * and corrective action may commence (remove or mark invalid any entries with different
 * signatures).
 * If you have no records which match this url_sig and key - no corrective action should
 * be taken as this packet may have been returned by an imposter.  
 *
 * @param[in,out] App &$a
 */

