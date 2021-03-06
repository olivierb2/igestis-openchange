<?php

namespace Igestis\Modules\OpenChange;

/**
 * Thie class is designed to update all OpenChange fields in the ldap database
 *
 * @author Gilles Hemmerlé
 */
use Igestis\Modules\OpenChange\ConfigModuleVars;

class OpenChangeLdapUpdate {

    /**
     *
     * @var \CoreContacts Contact to update in LDAP
     */
    private $contact;

    /**
     * Constructor will update all datas in ldap to integrate the employee in the ldap OpenChange environment
     * @param \CoreContacts $contact
     */
    public function __construct(\CoreContacts $contact) {
        
        // If the ldap synchronization is not activated, quit the script
        if(\ConfigIgestisGlobalVars::useLdap() != true) return;
        
        $this->contact = $contact;
        if ($this->contact->getUser()->getUserType() != "employee")
            return;

        try {
            // Connect the ldap database
        	$ldap = new \LDAP(\ConfigIgestisGlobalVars::ldapUris(), \ConfigIgestisGlobalVars::ldapBase());
         
        	if(\ConfigIgestisGlobalVars::ldapCustomBind()) {
        		$ldap->bind(str_replace("%u", \ConfigIgestisGlobalVars::ldapAdmin(), \ConfigIgestisGlobalVars::ldapCustomBind()), \ConfigIgestisGlobalVars::ldapPassword());
        	}
        	else {
        		$ldap->bind(\ConfigIgestisGlobalVars::LDAP_ADMIN, \ConfigIgestisGlobalVars::LDAP_PASSWORD);
        	};

            // Search the person
            $nodesList = $ldap->find("(cn=" . $this->contact->getLogin() . ")");      
            
            
            // If none found, quit this script, the person should be created from the main script, if not, we cannot update it...
            if(!$nodesList) return;

            
            
            // Global datas
            $ldapArray = array(
                "displayname" => $this->contact->getLogin(),
                "homemdb" => "CN=Mailbox Store (" . ConfigModuleVars::serverName . "),CN=First Storage Group,CN=InformationStore,CN=" . ConfigModuleVars::serverName . ",CN=Servers,CN=First Administrative Group,CN=Administrative Groups,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration," . \ConfigIgestisGlobalVars::LDAP_BASE,
            	"homemta" => "CN=Mailbox Store (" . ConfigModuleVars::serverName . "),CN=First Storage Group,CN=InformationStore,CN=" . ConfigModuleVars::serverName . ",CN=Servers,CN=First Administrative Group,CN=Administrative Groups,CN=First Organization,CN=Microsoft Exchange,CN=Services,CN=Configuration," . \ConfigIgestisGlobalVars::LDAP_BASE,
            	"legacyexchangedn" => "/o=First Organization/ou=First Administrative Group/cn=Recipients/cn=" . $this->contact->getLogin(),
            	"mailnickname" => $this->contact->getLogin(),
            	"msexchuseraccountcontrol" => 0,
            	"proxyaddresses" => array(
            			"=EX:/o=First Organization/ou=First Administrative Group/cn=Recipients/cn=" . $this->contact->getLogin(),
            			"smtp:" . ConfigModuleVars::postMaster,
            			"X400:c=US;a= ;p=First Organizati;o=Exchange;s=" . $this->contact->getLogin(),
            			"SMTP:" . $this->contact->getEmail()
            			)
            		
              );

            $node = $nodesList->getFirstNode();
            
            /*
            var_dump($node);
            exit;
            foreach ($attrs as $attribute) {
            	echo($attribute);
            }
            
            exit;
            foreach ($nodesList as $node) {
            	var_dump($node);
            	exit;
            	$node->modify($nodesList, $ldapArray);
            }
            */
            $node->modify($ldapArray);

            
            /*
            // Launch the update in the ldap database
            foreach ($nodesList as $node) {
                $node->modify($ldap->mergeArrays($nodesList, $ldapArray));
            }
            */
            

            
        } catch (Exception $exc) {
            new \wizz(_("Problem during the openchange ldap update") . (\ConfigIgestisGlobalVars::DEBUG_MODE ? "<br />" . $exc->getTraceAsString() : ""), \wizz::$WIZZ_WARNING);
        }
    }    
    
}
