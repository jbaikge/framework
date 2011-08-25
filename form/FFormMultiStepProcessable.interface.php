<?php
interface FFormMultiStepProcessable extends FFormProcessable {
	public function getMaxFormStep();
}

class FFormMultiStepProcessableDriver extends FFormProcessableDriver {
	private $step = 0;
	public function getFormStep () {
		return $this->step;
	}
	public function incrementStep () {
		return $this->setFormStep($this->step + 1);
	}
	/*!
	 * Generates an array of form field objects. This method is used
	 * primarily inside of FForm to build the fields included in a form
	 * based on the model definition in the primary class. Any options
	 * defined in a field's @b global and @b form context will be applied
	 * to each field returned by this method. This method does not have any
	 * use if called directly as all fields will be blank at all times. 
	 * 
	 * @see FField
	 * @return Array of FField subclassed objects with their options applied.
	 */
	public function makeFields() {
		$fields = parent::makeFields();
		$step = (int)$this->subject->getFormStep();
		$num_fields = count($fields);
		for ($i = 0; $i < $num_fields; $i++) {
			if ($fields[$i]->get('step', 0) != $step) {
				unset($fields[$i]);
			}
		}
		// Re-index:
		$fields = array_values($fields);
		// Add hidden field to store persistient data
		$fields[] = FFormFieldFactory::make('FHiddenField', '_formstate')->optional(true);
		return $fields;
	}
	public function restoreFromFormState () {
		if ($this->subject->_formstate) {
			$base64 = $this->subject->_formstate;
			$encrypted = base64_decode($base64);
			$json = mcrypt_decrypt(MCRYPT_BLOWFISH, $_ENV['config']['secret'], $encrypted, MCRYPT_MODE_CBC, $this->getIV());
			// Thanks, http://www.php.net/manual/en/function.mcrypt-decrypt.php#54734
			$json = rtrim($json, "\0");
			$data = json_decode($json);
			foreach ($data as $key => $value) {
				if (!$this->subject->$key) {
					$this->subject->$key = $value;
				}
			}
		}
	}
	public function saveFormState () {
		$data = $this->subject->getData();
		unset($data['_formstate']);
		$json = json_encode($data);
		$encrypted = mcrypt_encrypt(MCRYPT_BLOWFISH, $_ENV['config']['secret'], $json, MCRYPT_MODE_CBC, $this->getIV());
		$base64 = base64_encode($encrypted);
		$this->subject->_formstate = $base64;
	}
	public function setFormStep ($step) {
		if ($step >= 0 && $step <= $this->subject->getMaxFormStep()) {
			return $this->step = $step;
		} else {
			return false;
		}
	}
	/*!
	 * Generates the Initialization Vector since we are using a blowfish cypher.
	 */
	public function getIV () {
		return substr(md5(get_class($this->subject)), 0, 8);
	}
}

