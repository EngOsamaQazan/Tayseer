<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components;

use PhpAmqpLib\Message\AMQPMessage;
use yii\base\NotSupportedException;
use Yii;

/**
 * Amqp Queue.
 *
 * @deprecated since 2.0.2 and will be removed in 3.0. Consider using amqp_interop driver instead.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends \yii\queue\amqp\Queue
{


    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {

        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->open();
        $id = uniqid('', true);
        $this->channel->basic_publish(
            new AMQPMessage("$ttr;$message", [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $id,
            ]),
            $this->exchangeName
        );
        return $id;
    }

    public function listen()
    {
        $this->open();
        $callback = function (AMQPMessage $payload) {
            $id = $payload->get('message_id');
            list($ttr, $message) = explode(';', (string)$payload->body, 2);
            if ($this->handleMessage($id, $message, $ttr, 1)) {
                $payload->delivery_info['channel']->basic_ack($payload->delivery_info['delivery_tag']);
            }
        };

        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queueName, $this->queueName . '_everyoneWillBeDie', false, false, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function customListen()
    {
        $this->open();
        while (true) {
            $messageQueue = $this->channel->basic_get();
            if (isset($messageQueue)) {
                $id = $messageQueue->get('message_id');
                list($ttr, $message) = explode(';', (string)$messageQueue->body, 2);
                if ($this->handleMessage($id, $message, $ttr, 1)) {
                    $this->channel->basic_ack($messageQueue->delivery_info['delivery_tag']);
                }
                if ($messageQueue->delivery_info['message_count'] == 0) {
                    break;
                }
            } else {
                $this->channel->queue_delete($this->queueName);
               $this->close();
                return true;
            }
        }
        if ($messageQueue->delivery_info['message_count'] == 0) {
            $this->channel->queue_delete($this->queueName);
            $this->close();
        }
        return true;
    }
    public function Purge()
    {
        $this->open();
        $this->channel->queue_purge($this->queueName);
    }

}
