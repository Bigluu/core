<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Capabilities context.
 */
class CapabilitiesContext implements Context, SnippetAcceptingContext {

	use BasicStructure;
	use Provisioning;
	use Sharing;

	/**
	 * @Given /^parameter "([^"]*)" of app "([^"]*)" is set to "([^"]*)"$/
	 */
	public function serverParameterIsSetTo($parameter, $app, $value){
		$this->modifyServerConfig($app, $parameter, $value);
	}

	/**
	 * @Then /^fields of capabilities match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function checkCapabilitiesResponse(\Behat\Gherkin\Node\TableNode $formData){
		$capabilitiesXML = $this->response->xml()->data->capabilities;

		foreach ($formData->getHash() as $row) {
			$path_to_element = explode('@@@', $row['path_to_element']);
			$answeredValue = $capabilitiesXML->$row['capability'];
			for ($i = 0; $i < count($path_to_element); $i++){
				$answeredValue = $answeredValue->$path_to_element[$i];
			}
			$answeredValue = (string)$answeredValue;
			PHPUnit_Framework_Assert::assertEquals(
				$row['value']==="EMPTY" ? '' : $row['value'],
				$answeredValue,
				"Failed field " . $row['capability'] . " " . $row['path_to_element']
			);

		}
	}

	/**
	 * @BeforeScenario
	 */
	public function prepareParameters(){
		$this->modifyServerConfig('core', 'shareapi_enabled', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_links', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_public_upload', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_resharing', 'yes');
		$this->modifyServerConfig('files_sharing', 'outgoing_server2server_share_enabled', 'yes');
		$this->modifyServerConfig('files_sharing', 'incoming_server2server_share_enabled', 'yes');
		$this->modifyServerConfig('core', 'shareapi_enforce_links_password', 'no');
		$this->modifyServerConfig('core', 'shareapi_allow_public_notification', 'no');
		$this->modifyServerConfig('core', 'shareapi_default_expire_date', 'no');
		$this->modifyServerConfig('core', 'shareapi_enforce_expire_date', 'no');
	}

	/**
	 * @AfterScenario
	 */
	public function undoChangingParameters(){
		$this->modifyServerConfig('core', 'shareapi_enabled', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_links', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_public_upload', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_resharing', 'yes');
		$this->modifyServerConfig('files_sharing', 'outgoing_server2server_share_enabled', 'yes');
		$this->modifyServerConfig('files_sharing', 'incoming_server2server_share_enabled', 'yes');
		$this->modifyServerConfig('core', 'shareapi_enforce_links_password', 'no');
		$this->modifyServerConfig('core', 'shareapi_allow_public_notification', 'no');
		$this->modifyServerConfig('core', 'shareapi_default_expire_date', 'no');
		$this->modifyServerConfig('core', 'shareapi_enforce_expire_date', 'no');
	}

	/**
	 * @param string $app
	 * @param string $parameter
	 * @param string $value
	 */
	protected function modifyServerConfig($app, $parameter, $value) {
		$user = $this->currentUser;

		$this->currentUser = 'admin';

		$this->setStatusTestingApp(true);

		$body = new \Behat\Gherkin\Node\TableNode([['value', $value]]);
		$this->sendingToWith('post', "/apps/testing/api/v1/app/{$app}/{$parameter}", $body);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		$this->setStatusTestingApp(false);

		$this->currentUser = $user;
	}

	protected function setStatusTestingApp($enabled) {
		$this->sendingTo(($enabled ? 'post' : 'delete'), '/cloud/apps/testing');
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		$this->sendingTo('get', '/cloud/apps?filter=enabled');
		$this->theHTTPStatusCodeShouldBe('200');
		if ($enabled) {
			PHPUnit_Framework_Assert::assertContains('testing', $this->response->getBody()->getContents());
		} else {
			PHPUnit_Framework_Assert::assertNotContains('testing', $this->response->getBody()->getContents());
		}
	}
}
