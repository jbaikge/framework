<?php
class FObjectQueryFrom {
	private $preview;
	private $type = null;
	public function __construct ($type, $preview = false) {
		$this->type = $type;
		$this->preview = $preview;
		FObjectViewBuilder::buildIfExpired($this->type);
	}
	public function __call ($method, $args) {
		return;
	}
	public function __toString () {
		if ($_ENV['config']['fobject.qtables']) {
			$table_name = (($this->preview) ? 'qp_' : 'q_') . $this->type;
		} else {
			$table_name = (($this->preview) ? 'vp_' : 'v_') . $this->type;
		}
		$from = "  " . $table_name;
		return $from;
	}
}
