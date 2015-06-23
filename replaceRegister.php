<?php
/**
 * Replace teh default register system by own system
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2013 Denis Chenu <http://www.sondages.pro>
 * @copyright 2013 LimeSurvey Team <http://www.limesurvey.org>
 * @license GPL v3
 * @version 1.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
    class replaceRegister extends PluginBase
    {
        static protected $description = 'A plugin to replace the default register page ';
        static protected $name = 'replaceRegister';
        protected $storage = 'DbStorage';
        private $bUse = 1;
        private $bShowTokenForm = 1;
        private $sEmailTemplate = 'registration';

        protected $settings = array(
            'bUse' => array(
                'type' => 'select',
                'options'=>array(
                    0=>'No',
                    1=>'Yes'
                ),
                'default'=>1,
                'label' => 'Use replaceRegister by default.'
            ),
            'bShowTokenForm' => array(
                'type' => 'select',
                'options'=>array(
                    0=>'No',
                    1=>'Yes'
                ),
                'default'=>0,// Find a way to replace by $this->bUse
                'label' => 'Show a form to enter an existing token after registration form (Not used actually).'
            ),
            'sEmailTemplate' => array(
                'type' => 'select',
                'label' => 'Email template to use if email is already in token table.',
                'default'=> 'registration',
                'options' => array(
                    "registration"=>"Registration",
                    "invitation"=>"Invitation",
                    "reminder"=>"Reminder",
                    "none"=>"None (don't send an email)",
                )
            ),
        );
        // TODO :add prefill register system (in option, for hidden attribute)
        public function __construct(PluginManager $manager, $id) {
            parent::__construct($manager, $id);
            $this->subscribe('beforeSurveyPage');
            $this->subscribe('beforeSurveySettings');
            $this->subscribe('newSurveySettings');
        }
        function __init(){
            $bUse=$this->get('bUse');
            if(!is_null($bUse))
                $this->bUse=$bUse;
            $bShowTokenForm=$this->get('bShowTokenForm');
            if(!is_null($bShowTokenForm))
                $this->bShowTokenForm=$bShowTokenForm;
            $sEmailTemplate=$this->get('sEmailTemplate');
            if(!is_null($sEmailTemplate))
                $this->sEmailTemplate=$sEmailTemplate;
        }
        public function beforeSurveySettings()
        {
            $oEvent = $this->event;
            $iSurveyId=$oEvent->get('survey');
            $bUse=$this->get('use', 'Survey', $iSurveyId);
            $bShowTokenForm=$this->get('bShowTokenForm', 'Survey', $iSurveyId);
            $sEmailTemplate=$this->get('sEmailTemplate', 'Survey', $iSurveyId);
            // If is_null : get default
            //$bUse=!is_null($bUse)?$bUse:$this->bUse;
            $oEvent->set("surveysettings.{$this->id}", array(
                'name' => get_class($this),
                'settings' => array(
                    'bUse' => array(
                        'type' => 'select',
                        'options'=>array(
                            0=>'No',
                            1=>'Yes'
                        ),
                        'default'=>$this->bUse,
                        'tab'=>'tokens', // Setting no used yet
                        'label' => 'Use register from plugin replaceRegister',
                        'current' => $bUse
                    ),
                    'bShowTokenForm' => array(
                        'type' => 'select',
                        'options'=>array(
                            0=>'No',
                            1=>'Yes'
                        ),
                        'default'=>$this->bShowTokenForm,
                        'tab'=>'tokens', // Setting no used yet
                        'label' => 'Show the token form after the register form (if use replace register)',
                        'current' => $bShowTokenForm
                    ),
                    'sEmailTemplate' => array(
                        'type' => 'select',
                        'options'=>array(
                            "registration"=>"Registration",
                            "invitation"=>"Invitation",
                            "reminder"=>"Reminder",
                            "none"=>"None (don't send an email)",
                        ),
                        'default'=>$this->sEmailTemplate,
                        'tab'=>'tokens', // Setting no used yet
                        'label' => 'Email template to use if email is already in token table',
                        'current' => $sEmailTemplate
                    ),
                )
            ));
        }
        public function newSurveySettings()
        {
            $oEvent = $this->getEvent();
            self::__init();
            foreach ($oEvent->get('settings') as $name => $value)
            {
                $this->set($name, $value, 'Survey', $oEvent->get('survey'));
            }
        }

        public function beforeSurveyPage()
        {
            $oEvent = $this->event;
            $iSurveyId = $oEvent->get('surveyId');

            self::__init();
            $bUse=$this->get('bUse', 'Survey', $iSurveyId);
            if(is_null($bUse))
                $bUse=$this->bUse;
            if(!$bUse)
                return;

            $sToken= Yii::app()->request->getParam('token');
            if($iSurveyId && !$sToken)// Test invalid token ?
            {
                // Get the survey model
                $oSurvey=Survey::model()->find("sid=:sid",array(':sid'=>$iSurveyId));
                if($oSurvey && $oSurvey->active=="Y" && $oSurvey->allowregister=="Y" && tableExists("tokens_{$iSurveyId}"))
                {
                    // Fill parameters
                    $bShowTokenForm=$this->get('bShowTokenForm', 'Survey', $iSurveyId);
                    if(is_null($bShowTokenForm))
                        $bShowTokenForm=$this->bShowTokenForm;
                    $bShowTokenForm=$this->get('use', 'Survey', $iSurveyId);
                    if(is_null($bShowTokenForm))
                        $bShowTokenForm=$this->bUse;
                    Yii::app()->getClientScript()->registerCssFile(Yii::app()->getConfig('publicurl')."plugins/replaceRegister/css/register.css");
                    // We can go
                    $sLanguage = Yii::app()->request->getParam('lang','');
                    if ($sLanguage=="" )
                    {
                        $sLanguage = Survey::model()->findByPk($iSurveyId)->language;
                    }
                    $aSurveyInfo=getSurveyInfo($iSurveyId,$sLanguage);
                    $sAction= Yii::app()->request->getParam('action','view') ;
                    $sHtmlRegistererror="";
                    $sHtmlRegistermessage1=gT("You must be registered to complete this survey");;
                    $sHtmlRegistermessage2=gT("You may register for this survey if you wish to take part.")."<br />\n".gT("Enter your details below, and an email containing the link to participate in this survey will be sent immediately.");
                    $sHtmlRegisterform="";
                    $sHtml="";
                    $bShowForm=true;
                    $bValidMail=false;
                    $bTokenCreate=true;
                    $aExtraParams=array();
                    $aRegisterError=array();
                    $sR_email= Yii::app()->request->getPost('register_email');
                    $sR_firstname= sanitize_xss_string(Yii::app()->request->getPost('register_firstname',""));
                    $sR_lastname= sanitize_xss_string(Yii::app()->request->getPost('register_lastname',""));
                    $sR_lastname= sanitize_xss_string(Yii::app()->request->getPost('register_lastname',""));
                    $aR_attribute=array();
                    $aR_attributeGet=array();
                    $aExtraParams=array();
                    $aMail=array();
                    foreach ($aSurveyInfo['attributedescriptions'] as $field => $aAttribute)
                    {
                        if (!empty($aAttribute['show_register']) && $aAttribute['show_register'] == 'Y')
                        {
                            $aR_attribute[$field]= sanitize_xss_string(Yii::app()->request->getPost('register_'.$field),"");// Need to be filtered ?
                        }
                        elseif($aAttribute['description']==sanitize_paranoid_string($aAttribute['description']) && trim(Yii::app()->request->getQuery($aAttribute['description'],"")) )
                        {
                            $aR_attributeGet[$field]= sanitize_xss_string(trim(Yii::app()->request->getQuery($aAttribute['description'],"")));// Allow prefill with URL (TODO: add an option)
                            $aExtraParams[$aAttribute['description']]=sanitize_xss_string(trim(Yii::app()->request->getParam($aAttribute['description'],"")));
                        }
                    }
                    if($sAction=='register' && !is_null($sR_email) && Yii::app()->request->getPost('changelang')!='changelang')
                    {
                        $bShowForm=false;
                        // captcha
                        $sLoadsecurity=Yii::app()->request->getPost('loadsecurity');
                        $sSecAnswer=(isset($_SESSION['survey_'.$iSurveyId]['secanswer']))?$_SESSION['survey_'.$iSurveyId]['secanswer']:"";
                        $bShowForm=false;
                        $bNoError=true;
                        // Copy paste RegisterController
                        if($sR_email)
                        {
                            //Check that the email is a valid style addressattribute_2
                            if (!validateEmailAddress($sR_email))
                            {
                                $aRegisterError[]= gT("The email you used is not valid. Please try again.");
                            }
                        }
                        else
                        {
                            $aRegisterError[]= gT("The email you used is not valid. Please try again.");// Empty email
                        }
                        // Fill and validate mandatory extra attribute
                        foreach ($aSurveyInfo['attributedescriptions'] as $field => $aAttribute)
                        {
                            if (!empty($aAttribute['show_register']) && $aAttribute['show_register'] == 'Y' && $aAttribute['mandatory'] == 'Y' && ($aR_attribute[$field]=="" || is_null($aR_attribute[$field])) )
                            {
                                $aRegisterError[]= sprintf(gT("%s cannot be left empty").".", $aSurveyInfo['attributecaptions'][$field]);
                            }
                        }
                        // Check the security question's answer : at end because the security question is the last one
                        if (function_exists("ImageCreate") && isCaptchaEnabled('registrationscreen',$aSurveyInfo['usecaptcha']) )
                        {
                            if (!$sLoadsecurity || !$sSecAnswer || $sLoadsecurity != $sSecAnswer)
                            {
                                $aRegisterError[]= gT("The answer to the security question is incorrect.");
                            }
                        }
                        if(count($aRegisterError)==0)
                        {
                            //Check if this email already exists in token database
                            $oToken=TokenDynamic::model($iSurveyId)->find('email=:email',array(':email'=>$sR_email));
                            if ($oToken)
                            {
                                if($oToken->usesleft<1 && $aSurveyInfo['alloweditaftercompletion']!='Y')
                                {
                                    $aRegisterError="The e-mail address you have entered is already registered an the questionnaire has been completed.";
                                }
                                elseif(strtolower(substr(trim($oToken->emailstatus),0,6))==="optout")// And global blacklisting ?
                                {
                                    $aRegisterError="This email address is already registered but someone ask to don't receive new email again.";
                                }
                                elseif(!$oToken->emailstatus && $oToken->emailstatus!="OK")
                                {
                                    $aRegisterError="This email address is already registered but the email adress was bounced.";
                                }
                                else
                                {
                                    $iTokenId=$oToken->tid;
                                    $aMail['subject']=$aSurveyInfo['email_register_subj'];
                                    $aMail['message']=$aSurveyInfo['email_register'];
                                    $aMail['information']="The address you have entered is already registered. An email has been sent to this address with a link that gives you access to the survey.";
                                    // Did we update the token ? Setting ?
                                }
                            }
                            else
                            {
                                $oToken= Token::create($iSurveyId);
                                $oToken->firstname = $sR_firstname;
                                $oToken->lastname = $sR_lastname;
                                $oToken->email = $sR_email;
                                $oToken->emailstatus = 'OK';
                                $oToken->language = $sLanguage;
                                $oToken->setAttributes($aR_attribute);
                                $oToken->setAttributes($aR_attributeGet);// Need an option
                                if ($aSurveyInfo['startdate'])
                                {
                                    $oToken->validfrom = $aSurveyInfo['startdate'];
                                }
                                if ($aSurveyInfo['expires'])
                                {
                                    $oToken->validuntil = $aSurveyInfo['expires'];
                                }
                                $oToken->save();
                                $iTokenId=$oToken->tid;
                                TokenDynamic::model($iSurveyId)->createToken($iTokenId);// Review if really create a token
                                $aMail['subject']=$aSurveyInfo['email_register_subj'];
                                $aMail['message']=$aSurveyInfo['email_register'];
                                $aMail['information']=gT("An email has been sent to the address you provided with access details for this survey. Please follow the link in that email to proceed.");
                            }
                        }
                    }
                    if($aMail && $oToken)
                    {
                        $aReplacementFields=array();
                        $aReplacementFields["{ADMINNAME}"]=$aSurveyInfo['adminname'];
                        $aReplacementFields["{ADMINEMAIL}"]=$aSurveyInfo['adminemail'];
                        $aReplacementFields["{SURVEYNAME}"]=$aSurveyInfo['name'];
                        $aReplacementFields["{SURVEYDESCRIPTION}"]=$aSurveyInfo['description'];
                        $aReplacementFields["{EXPIRY}"]=$aSurveyInfo["expiry"];
                        $oToken=TokenDynamic::model($iSurveyId)->findByPk($iTokenId);
                        foreach($oToken->attributes as $attribute=>$value){
                            $aReplacementFields["{".strtoupper($attribute)."}"]=$value;
                        }
                        $sToken=$oToken->token;
                        $aMail['subject']=preg_replace("/{TOKEN:([A-Z0-9_]+)}/","{"."$1"."}",$aMail['subject']);
                        $aMail['message']=preg_replace("/{TOKEN:([A-Z0-9_]+)}/","{"."$1"."}",$aMail['message']);
                        $surveylink = App()->createAbsoluteUrl("/survey/index/sid/{$iSurveyId}",array('lang'=>$sLanguage,'token'=>$sToken));
                        $optoutlink = App()->createAbsoluteUrl("/optout/tokens/surveyid/{$iSurveyId}",array('langcode'=>$sLanguage,'token'=>$sToken));
                        $optinlink = App()->createAbsoluteUrl("/optin/tokens/surveyid/{$iSurveyId}",array('langcode'=>$sLanguage,'token'=>$sToken));
                        if (getEmailFormat($iSurveyId) == 'html')
                        {
                            $useHtmlEmail = true;
                            $aReplacementFields["{SURVEYURL}"]="<a href='$surveylink'>".$surveylink."</a>";
                            $aReplacementFields["{OPTOUTURL}"]="<a href='$optoutlink'>".$optoutlink."</a>";
                            $aReplacementFields["{OPTINURL}"]="<a href='$optinlink'>".$optinlink."</a>";
                        }
                        else
                        {
                            $useHtmlEmail = false;
                            $aReplacementFields["{SURVEYURL}"]= $surveylink;
                            $aReplacementFields["{OPTOUTURL}"]= $optoutlink;
                            $aReplacementFields["{OPTINURL}"]= $optinlink;
                        }
                        // Allow barebone link for all URL
                        $aMail['message'] = str_replace("@@SURVEYURL@@", $surveylink, $aMail['message']);
                        $aMail['message'] = str_replace("@@OPTOUTURL@@", $optoutlink, $aMail['message']);
                        $aMail['message'] = str_replace("@@OPTINURL@@", $optinlink, $aMail['message']);
                        // Replace the fields
                        $aMail['subject']=ReplaceFields($aMail['subject'], $aReplacementFields);
                        $aMail['message']=ReplaceFields($aMail['message'], $aReplacementFields);
                        
                        // We have it, then try to send the mail.
                        $from = "{$aSurveyInfo['adminname']} <{$aSurveyInfo['adminemail']}>";
                        $sitename =  Yii::app()->getConfig('sitename');
                        if (SendEmailMessage($aMail['message'], $aMail['subject'], $sR_email, $from, $sitename,$useHtmlEmail,getBounceEmail($iSurveyId)))
                        {
                            // TLR change to put date into sent
                            $today = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig('timeadjust'));
                            $oToken->sent=$today;
                            $oToken->save();
                            $sReturnHtml="<div id='wrapper' class='message tokenmessage'>"
                                . "<p>".gT("Thank you for registering to participate in this survey.")."</p>\n"
                                . "<p>".$aMail['information']."</p>\n"
                                . "<p>".gT("Survey administrator")." {ADMINNAME} ({ADMINEMAIL})</p>"
                                . "</div>\n";

                        }
                        else
                        {
                            $sReturnHtml="<div id='wrapper' class='message tokenmessage'>"
                                . "<p>".gT("Thank you for registering to participate in this survey.")."</p>\n"
                                . "<p>"."We can not sent you an email actually, please contact the survey administrator"."</p>\n"
                                . "<p>".gT("Survey administrator")." {ADMINNAME} ({ADMINEMAIL})</p>"
                                . "</div>\n";
                        
                        }
                        $sReturnHtml=ReplaceFields($sReturnHtml, $aReplacementFields);
                        $sTemplatePath=$aData['templatedir'] = getTemplatePath($aSurveyInfo['template']);
                        ob_start(function($buffer, $phase) {
                            App()->getClientScript()->render($buffer);
                            App()->getClientScript()->reset();
                            return $buffer;
                        });
                        ob_implicit_flush(false);
                        sendCacheHeaders();
                        doHeader();
                        $aData['thissurvey'] = $aSurveyInfo;
                        $aData['thissurvey'] = $aSurveyInfo;
                        echo templatereplace(file_get_contents($sTemplatePath.'/startpage.pstpl'),array(), $aData);
                        echo templatereplace(file_get_contents($sTemplatePath.'/survey.pstpl'),array(), $aData);
                        echo $sReturnHtml;
                        echo templatereplace(file_get_contents($sTemplatePath.'/endpage.pstpl'),array(), $aData);
                        doFooter();
                        ob_flush();
                        App()->end();
                    }
                    if($bShowForm || count($aRegisterError))
                    {
                        // Language ?
                        if(count($aRegisterError)==1){
                            $sHtmlRegistererror="<p class='error error-register'><strong>{$aRegisterError[0]}</strong></p>";
                        }elseif(count($aRegisterError)>1){
                            $sHtmlRegistererror="<ul class='error error-register error-list'>";
                            foreach ($aRegisterError as $sRegisterError)
                                $sHtmlRegistererror.="<li><strong>{$sRegisterError}</strong></li>";
                            $sHtmlRegistererror.="</ul>";
                        }
                        $aExtraParams['action']='register';
                        $aExtraParams['lang']=$sLanguage;
                        $sHtmlRegisterform = CHtml::form(Yii::app()->createUrl("/survey/index/sid/{$iSurveyId}",$aExtraParams), 'post');
                        $sHtmlRegisterform.="<table class='register'><tbody>\n";
                        $sHtmlRegisterform.=  "<tr><th><label for='register_firstname'>".gT("First name") . "</label></th><td>".CHtml::textField('register_firstname',htmlentities($sR_firstname, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        $sHtmlRegisterform.=  "<tr><th><label for='register_lastname'>".gT("Last name") . "</label></th><td>".CHtml::textField('register_lastname',htmlentities($sR_lastname, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        $sHtmlRegisterform.=  "<tr class='mandatory'><th><label for='register_email'>".gT("Email address") . "</label></th><td>".CHtml::textField('register_email',htmlentities($sR_email, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        // Extra attribute
                        foreach ($aSurveyInfo['attributedescriptions'] as $field => $aAttribute)
                        {
                            if (!empty($aAttribute['show_register']) && $aAttribute['show_register'] == 'Y')
                            {
                                $sHtmlRegisterform.=  "<tr".($aAttribute['mandatory'] == 'Y' ? " class='mandatory'" : '')."><th><label for='register_{$field}'>".$aSurveyInfo['attributecaptions'][$field].($aAttribute['mandatory'] == 'Y' ? ' *' : '')."</label></th><td>".CHtml::textField('register_'.$field,htmlentities($aR_attribute[$field], ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                            }
                        }
                        if (function_exists("ImageCreate") && isCaptchaEnabled('registrationscreen', $aSurveyInfo['usecaptcha']))
                            $sHtmlRegisterform.= "<tr><th><label for='loadsecurity'>" . gT("Security question") . "</label></th><td><img src='".Yii::app()->getController()->createUrl("/verification/image/sid/{$iSurveyId}")."' alt='' /><input type='text' size='5' maxlength='3' name='loadsecurity' id='loadsecurity' value='' /></td></tr>\n";
                        $sHtmlRegisterform.= "<tr><td></td><td>".CHtml::submitButton(gT("Continue"))."</td></tr>";
                        $sHtmlRegisterform.= "</tbody></table>\n";
                        $sHtmlRegisterform.= makeLanguageChangerSurvey($sLanguage);// Need to be inside the form
                        $sHtmlRegisterform.= CHtml::endForm();
                    }
                    $sTemplatePath=$aData['templatedir'] = getTemplatePath($aSurveyInfo['template']);
                    ob_start(function($buffer, $phase) {
                        App()->getClientScript()->render($buffer);
                        App()->getClientScript()->reset();
                        return $buffer;
                    });
                    ob_implicit_flush(false);
                    sendCacheHeaders();
                    doHeader();
                    // Get the register.pstpl file content, but remplace default by own string
                    $sHtmlRegister=file_get_contents($sTemplatePath.'/register.pstpl');
                    $sHtmlRegister= str_replace("{REGISTERERROR}",$sHtmlRegistererror,$sHtmlRegister);
                    $sHtmlRegister= str_replace("{REGISTERMESSAGE1}",$sHtmlRegistermessage1,$sHtmlRegister);
                    $sHtmlRegister= str_replace("{REGISTERMESSAGE2}",$sHtmlRegistermessage2,$sHtmlRegister);
                    $sHtmlRegister= str_replace("{REGISTERFORM}",$sHtmlRegisterform,$sHtmlRegister);

                    $aData['thissurvey'] = $aSurveyInfo;
                    echo templatereplace(file_get_contents($sTemplatePath.'/startpage.pstpl'),array(), $aData);
                    echo templatereplace(file_get_contents($sTemplatePath.'/survey.pstpl'),array(), $aData);
                    echo templatereplace($sHtmlRegister);
                    echo templatereplace(file_get_contents($sTemplatePath.'/endpage.pstpl'),array(), $aData);
                    doFooter();
                    ob_flush();
                    App()->end();
                }
            }
        }
    }
?>
