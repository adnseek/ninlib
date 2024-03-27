<?php

class TestQueue extends AppController
{

	public function render()
	{
		$content = $this->performAction();
		return $content;
	}

	public function processDeleteUserAction()
	{
		return $this->processTask('DeleteUser');
	}

	private function processTask($type) {
		$content = '';
		$counter = 0;
		$msgQ = new MessageQueue($type);

		while ($taskObj = $msgQ->getTaskObject()) {
			try {
				$content .= '<pre>' . ++$counter . '  ' . $taskObj->process($msgQ->getTaskData()) . '</pre>';
				$msgQ->setStatus(MessageQueue::STATUS_DONE);

			} catch (Exception $e) {
				$msgQ->setStatus(MessageQueue::STATUS_FAILED);
			}
		}
		return $content;
	}

	public function processNotifyUserAction() {
		return $this->processTask('NotifyUser');
	}

	public function createAction() {
		$taskData = '{"array":[1,2,3],"boolean":true,"null":null,"number":123,"object":{"a":"b","c":"d","e":"f"},"string":"Hello World"}';

		$msgQ = new MessageQueue('DeleteUser');
		$msgQ->createTask($taskData);
	}
}
