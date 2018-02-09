<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Subscriber Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Subscriber extends DealerData {
    protected $SubscriberData;
    protected $Defaults = [
        'demo' => 0,
        'id'   => 0,
        'name' => '',
        'email'=> ''
    ];

    function __construct() {
      global $db;
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___Subscriber init\n";

        $this->SubscriberData = $this->Defaults;

        // Determine if they are using a valid Demo
        $SubscriberData = (new Membership)->Attr();
        if ( isset($SubscriberData['auth.demo.length']) ) {
          // See if its expired
          $ExpirationTimestamp = (new Date)->Translate($SubscriberData['auth.demo.length']);
          if ( time() <= $ExpirationTimestamp )
            $this->SubscriberData['demo'] = 1;
        }
        /*
        if ( isset($_SESSION['Subscriber.Demo.ID']) && isset($_SESSION['Subscriber.Demo.PIN']) ) {
          if ( (new Membership)->AttemptLogin() ) // Can't use demo if you're logged in (for now any way)
            return;
          // Fetch the demo id and make sure its valid/not expired
          $get = $db->Fetch('dealerdata_demos_subscribers', 'created', 'id='.$_SESSION['Subscriber.Demo.ID'].' AND pin=\''.$_SESSION['Subscriber.Demo.PIN'].'\'');
          $DemoThresholdSeconds = (new Date)->Translate((new DealerData)->Config()->Get('Threshold.Demo'));
          if ( $get && time() - $get['created'] < $DemoThresholdSeconds )
            $this->SubscriberData['demo'] = $_SESSION['Subscriber.Demo.ID'];
        }
        */

        // Get user info for Attr

    } // End __construct() method

    public function Attr($AttributeName='*') {
        if ( $AttributeName == '*' )
            return $this->SubscriberData;

        return $this->SubscriberData[$AttributeName];
    }

    public function SubscriptionRequired() {
        if ( !$this->Attr('demo') && !(new Membership)->AttemptLogin() ) {
            header('Location: /login?r='.encode_string($_SERVER['REQUEST_URI']));
            return false;
        }

        return true;
    }


    function __destruct() {
    } // End __destruct() method
}
?>
