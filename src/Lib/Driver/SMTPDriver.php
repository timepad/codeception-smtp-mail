<?php

namespace Codeception\Lib\Driver;

use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Results;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;

/**
 * @author Ahmed Samy <ahmed.samy.cs@gmail.com>
 *         artyfarty <dmitry@timepad.ru>
 */
class SMTPDriver
{
    /** @var Horde_Imap_Client_Socket */
    private $socket;

    /** @var  Horde_Imap_Client_Mailbox */
    private $mailbox;

    /** @var  int */
    private $numberOfRetries;

    /** @var  int */
    private $waitIntervalInSeconds;

    public function __construct($config)
    {
        $this->socket = new Horde_Imap_Client_Socket(array(
            'username' => $config['username'],
            'password' => $config['password'],
            'hostspec' => $config['imap_path'],
            //'port' =>     $config['password'],
            //'secure' => 'tls',
        ));

        $this->mailbox = Horde_Imap_Client_Mailbox::get('INBOX');

        $this->numberOfRetries = $config['retry_counts'];
        $this->waitIntervalInSeconds = $config['wait_interval'];
    }

    /**
     * @param Horde_Imap_Client_Search_Query $criteria
     *
     * @throws \Exception
     * @return Horde_Imap_Client_Data_Fetch
     */
    public function getEmailBy($criteria)
    {
        $this->getEmailsBy($criteria)->first();
    }

    /**
     * @param Horde_Imap_Client_Search_Query $criteria
     *
     * @return Horde_Imap_Client_Fetch_Results
     * @throws \Exception
     */
    public function getEmailsBy($criteria)
    {
        $searchResult = $this->search($criteria);

        if (!$searchResult['count']){
            throw new \Exception(sprintf("No emails found with given criteria %s", $criteria));
        }

        $mailIds = $searchResult['match'];

        $fq = new \Horde_Imap_Client_Fetch_Query();
        $ids = new \Horde_Imap_Client_Ids($mailIds);

        $mails = $this->socket->fetch($this->mailbox, $fq, ['ids' => $ids]);

        return $mails;
    }

    /**
     * @param $criteria
     *
     * @return bool
     */
    public function seeEmailBy($criteria)
    {
        $result = $this->search($criteria);

        return !!$result['count'];
    }

    /**
     * @param Horde_Imap_Client_Data_Fetch $mail
     *
     * @return mixed
     */
    public function getLinksByEmail($mail)
    {
        $matches = [];

        preg_match_all('|href="([^\s"]+)|', $mail->getBodyText(), $matches);

        return $matches[1];
    }

    /**
     * @param Horde_Imap_Client_Search_Query $criteria
     *
     * @return array
     */
    protected function search($criteria)
    {
        return $this->retry(
            $criteria,
            $this->numberOfRetries,
            $this->waitIntervalInSeconds
        );
    }

    /**
     * @param Horde_Imap_Client_Search_Query $criteria
     * @param int    $numberOfRetries
     * @param int    $waitInterval
     *
     * @return array
     **/
    protected function retry($criteria, $numberOfRetries, $waitInterval)
    {
        $mailIds = [];
        while ($numberOfRetries > 0) {
            sleep($waitInterval);
            
            $result = $this->socket->search($this->mailbox, $criteria);
            
            if ($result['count']) {
                break;
            }
            
            $numberOfRetries--;
            codecept_debug("Failed to find the email, retrying ... ({$numberOfRetries}) trie(s) left");
        }

        return $mailIds;
    }
}
