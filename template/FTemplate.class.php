<?php
/**
 * Basic templating engine. The template path provided in the constructor is 
 * the final template included and returned with the call to FTemplate::render.  
 * Any variables declared in userland are available in the templates when 
 * called with FTemplate::get and FTemplate::render.
 *
 * @b Common @b Use: Templating a page with a content area
 * @code
 * $page = new FTemplate('templates/base.html.php');
 * $content = "Hello World!";
 * $page->render();
 * @endcode
 *
 * @b Advanced @b Use: Templating a thank you page and an email body to an 
 * administrator
 * @code
 * $page = new FTemplate('templates/base.html.php');
 * $heading = "Thank you for contacting us!";
 * // Template containing links to other parts of the site:
 * $content = $page->fetch('templates/view_sections.html.php', array());
 *
 * // Template contains the email body with variables referencing submission
 * $email_content = new FTemplate('templates/notify.email.php');
 * mail("admin@site.com", "New Contact", $email_content);
 *
 * $page->render();
 * @endcode
 *
 * @author Jacob Tews <jacob@webteks.com>
 * @date Fri Mar  7 21:36:17 EST 2008
 * @version $Id$
 */
class FTemplate {
	private $baseTemplate; ///< Path to the base template
	private $lastTemplatePath; ///< Path to last used template
	private $templateContents; ///< Contents of last used template
	/**
	 * Sets up this template
	 *
	 * @param $base_template_path The path to the base template used in 
	 * FTemplate::render. The path provided must exist within the 
	 * include_path
	 */
	public function __construct ($base_template_path) {
		$this->baseTemplate = $base_template_path;
		$this->lastTemplatePath = null;
		$this->templateContents = '';
	}
	/**
	 * Returns string representation of this template. This method is never 
	 * called directly - only when trying to represent this Object as a 
	 * String.
	 *
	 * @see FTemplate::render Called with a True argument
	 * @return Processed base template
	 */
	public function __toString () {
		return $this->render(true);
	}
	/**
	 * Returns a processed template as a string. All variables in userland 
	 * become availabe in the template as references to the real variables.
	 * Modifications to variables in the template may be reflected in the 
	 * calling script. 
	 *
	 * To increase the performance of this method, supply the optional 
	 * second parameter, $variables, with an associative array where the 
	 * key is the variable name and the value is the variable value. This 
	 * will cancel the extraction of userland variables and only used those 
	 * passed.
	 *
	 * To use templates that do not require variables at all, pass an empty 
	 * array in the second argument to prevent unnecessary processing.
	 *
	 * @param $template_path Path to the template. The template must exist 
	 * within the include_path
	 * @param $variables @b Optional Associative array of variables 
	 * specific to the template where the key is the variable name and the 
	 * value is the variable value
	 */
	public function fetch ($template_path, $variables = null) {
		if ($template_path != $this->lastTemplatePath) {
			if (!FFileSystem::fileExists($template_path)) {
				trigger_error("Could not retrieve template: {$template_path}", E_USER_WARNING);
				return '';
			}
			$this->templateContents = file_get_contents($template_path, FILE_USE_INCLUDE_PATH);
			$this->lastTemplatePath = $template_path;
		}
		if ($variables === null) {
			$variables =& $GLOBALS;
		}
		extract($variables, EXTR_REFS);
		ob_start();
		eval('?' . '>' . $this->templateContents);
		return ob_get_clean();
	}
	/**
	 * Builds the final template. Generally the last method called, the 
	 * processed contents of the base template (passed during construction) 
	 * are either echoed (default) or returned as a string.
	 *
	 * @param $return_output @b Optional Whether to return (True) or echo 
	 * template contents (False)
	 * @return Processed base template if $return_output is True.
	 */
	public function render ($return_output = false) {
		if ($return_output) {
			return $this->fetch($this->baseTemplate);
		} else {
			echo $this->fetch($this->baseTemplate);
		}
	}
}