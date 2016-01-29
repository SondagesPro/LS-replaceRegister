<?php
/**
 * Replace teh default register system by own system
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2013-2015 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 2.0
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
        private $bShowTokenForm = 0;
        private $bConfirmEmail = 0;

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
                'default'=>0,
                'label' => 'Show a form to enter an existing token after registration form (Not used actually).'
            ),
            'bConfirmEmail' => array(
                'type' => 'select',
                'options'=>array(
                    0=>'No',
                    1=>'Yes',
                ),
                'default'=>1,
                'label' => 'Show a form to enter an existing token after registration form (Not used actually).'
            ),
            'sEmailTemplate' => array(
                'type' => 'select',
                'label' => 'Email template to use if email is already in token table.',
                'default'=> 'registration',
                'options' => array(
                    "register"=>"Registration",
                    "invite"=>"Invitation",
                    "remind"=>"Reminder",
                    "none"=>"None (don't send an email, and show an error)",
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

            $this->bConfirmEmail=$this->get('bConfirmEmail',null,null,$this->settings['bConfirmEmail']['default']);

            $sEmailTemplate=$this->get('sEmailTemplate');
            if(!is_null($sEmailTemplate))
                $this->sEmailTemplate=$sEmailTemplate;
        }
        public function beforeSurveySettings()
        {
            $oEvent = $this->event;
            $iSurveyId=$oEvent->get('survey');
            $bUse=$this->get('use', 'Survey', $iSurveyId);
            $bShowTokenForm=$this->get('bShowTokenForm', 'Survey', $iSurveyId,$this->bShowTokenForm);
            $bConfirmEmail=$this->get('bConfirmEmail', 'Survey', $iSurveyId,$this->bConfirmEmail);
            $sEmailTemplate=$this->get('sEmailTemplate', 'Survey', $iSurveyId,$this->sEmailTemplate);

            $assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets');
            Yii::app()->clientScript->registerCssFile(Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets/settingsfix.css'));
            $aTokenInformations=$this->getTokenInformations($iSurveyId);
            // If is_null : get default
            //$bUse=!is_null($bUse)?$bUse:$this->bUse;
            $aSettings=array(
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
                'bRedirectWithout' => array(
                    'type' => 'select',
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Go to the survey after registering',
                    'current' => $this->get('bRedirectWithout', 'Survey', $iSurveyId,0),
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
                'bConfirmEmail' => array(
                    'type' => 'select',
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'default'=>$this->bConfirmEmail,
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Add an input for email confirmation',
                    'current' => $bConfirmEmail
                ),
                'sEmailTemplate' => array(
                    'type' => 'select',
                    'options'=>array(
                        "register"=>"Registration",
                        "invite"=>"Invitation",
                        "remind"=>"Reminder",
                        "none"=>"None (don't send an email, and show an error)",
                    ),
                    'default'=>$this->sEmailTemplate,
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Email template to use if email is already in token table',
                    'current' => $sEmailTemplate
                ),
                'bFirstnameMandatroy'=>array(
                    'type' => 'select',
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Set firstname mandatory',
                    'current' => $this->get('bFirstnameMandatroy', 'Survey', $iSurveyId,0),
                ),
                'bLastnameMandatroy'=>array(
                    'type' => 'select',
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Set lastname mandatory',
                    'current' => $this->get('bLastnameMandatroy', 'Survey', $iSurveyId,0),
                ),
                'bEmailMandatroy'=>array(
                    'type' => 'select',
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'tab'=>'tokens', // Setting no used yet
                    'label' => 'Email is mandatory',
                    'current' => $this->get('bEmailMandatroy', 'Survey', $iSurveyId,1),
                ),
                'aDuplicateControl'=> array(
                    'type'=>'select',
                    'label'=>gt("Duplicates are determined by:"),
                    'options'=>$aTokenInformations['aShownAttribute'],
                    'htmlOptions'=>array(
                        'multiple'=>'multiple',
                    ),
                    'selectOptions'=>array(
                        'width' => null,
                        'minimumResultsForSearch'=> 5,
                    ),
                    'current'=>$aTokenInformations['aDuplicateControl'],
                ),
                'sAttributeDate' => array(
                    'type'=>'select',
                    'label'=>gt("Attribute with date"),
                    'options'=>$aTokenInformations['aUsableAttribute'],
                    'htmlOptions'=>array(
                        'empty'=>gt("None"),
                    ),
                    'current'=>$this->get('sAttributeDate', 'Survey', $iSurveyId),
                ),
                'sAttributeDateMin' => array(
                    'type'=>'string',
                    'label'=>gt("Date minimum"),
                    'help'=>gt("You can use PHP expression (ie : 18 years ago), defaut to 100 years ago"),
                    'current'=>$this->get('sAttributeDateMin', 'Survey', $iSurveyId),
                ),
                'sAttributeDateMax' => array(
                    'type'=>'string',
                    'label'=>gt("Date maximum"),
                    'help'=>gt("You can use PHP expression (ie : 18 years ago), defaut to now"),
                    'current'=>$this->get('sAttributeDateMax', 'Survey', $iSurveyId),
                ),
                'sPrefillWithDate' => array(
                    'type'=>'select',
                    'label'=>gt("Attribute to prefill with date"),
                    'options'=>$aTokenInformations['aHiddenAttribute'],
                    'htmlOptions'=>array(
                        'empty'=>gt("None"),
                    ),
                    'current'=>$this->get('sPrefillWithDate', 'Survey', $iSurveyId, 0),
                ),
                'sPrefillDateSystem' => array(
                    'type'=>'select',
                    'label'=>gt("Date format to prefill"),
                    'options'=>array(
                        'year'=>"This year",
                        'school'=>"School year (July to July)",
                        'monthyear'=>"Mont + year : MM-DD",
                        'all'=>"Complet : YYYY-MM-DD",

                    ),
                    'current'=>$this->get('sPrefillDateSystem', 'Survey', $iSurveyId, 'school'),
                ),
                'sPrefillDateDuplicate' => array(
                    'type'=>'select',
                    'label'=>gt("Use it for duplicate"),
                    'options'=>array(
                        0=>'No',
                        1=>'Yes'
                    ),
                    'current'=>$this->get('sPrefillDateDuplicate', 'Survey', $iSurveyId, 1),
                ),
            );
            // Remove invalid
            // Add language : @todo multi-lingual + default (multi-lingual too)
            $aSettings['sDescriptionText']=array(
                'type'=>'html',
                'label'=>'Description of the form',
                'help'=>'Replace the default description from LimeSurvey',
                'current'=>$this->get('sDescriptionText', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sErrorCompleted']=array(
                'type'=>'string',
                'label'=>'Error sentence to show if survey is already completed',
                'help'=>"The email address you have entered is already registered an the questionnaire has been completed.",
                'current'=>$this->get('sErrorCompleted', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sErrorOptoput']=array(
                'type'=>'string',
                'label'=>'Error sentence to show if email is set but user ask to not reive new email',
                'help'=> "This email address is already registered but someone ask to don't receive new email again.",
                'current'=>$this->get('sErrorOptoput', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sErrorBounced']=array(
                'type'=>'string',
                'label'=>'Error sentence to show if email is bouced (on error)',
                'help'=>"This email address is already registered but the email adress was bounced.",
                'current'=>$this->get('sErrorBounced', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sErrorNoemail']=array(
                'type'=>'string',
                'label'=>'Error sentence to show if email is not set with information provided',
                'help'=>"The information you have entered is already registered but no email is set.",
                'current'=>$this->get('sErrorNoemail', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sSuccessRegistred']=array(
                'type'=>'string',
                'label'=>'Sentence to show if email is already registred, when sending a new email',
                'help'=>"The address you have entered is already registered. An email has been sent to this address with a link that gives you access to the survey.",
                'current'=>$this->get('sSuccessRegistred', 'Survey', $iSurveyId, ""),
            );
            $aSettings['sDescriptionToken']=array(
                'type'=>'html',
                'label'=>'Description of the token form',
                'help'=>gT("If you have been issued a token, please enter it in the box below and click continue."),
                'current'=>$this->get('sDescriptionToken', 'Survey', $iSurveyId, ""),
            );
            if(empty($aTokenInformations['aUsableAttribute']))
            {
                $aSettings['sAttributeDate']=array(
                    'type'=>'info',
                    'label'=>gt("Attribute with date"),
                    'content'=>"No valid attribute",
                );
            }
            if(empty($aTokenInformations['aHiddenAttribute']))
            {
                $aSettings['sPrefillWithDate']=array(
                    'type'=>'info',
                    'label'=>gt("Attribute to prefill with date"),
                    'content'=>CHtml::textField("","Non valid attribute",array("disabled"=>true))

                    //~ 'content'=>'<div class="default control-label col-sm-5">Attribute with date</div><div class="default col-sm-7 controls"><input type="text" disabled value="" /></div>'
                );
            }
            $oEvent->set("surveysettings.{$this->id}", array( 'name' => get_class($this),'settings'=>$aSettings));

        }
        public function newSurveySettings()
        {
            $oEvent = $this->getEvent();
            $aSetting=$oEvent->get('settings');
            if(empty($aSetting['aDuplicateControl']))
                $aSetting['aDuplicateControl'][]="email";
            self::__init();
            foreach ($aSetting as $name => $value)
            {
                if($name=='aDuplicateControl')
                    $value=json_encode($value);
                $this->set($name, $value, 'Survey', $oEvent->get('survey'));
            }
        }

        public function beforeSurveyPage()
        {
            $oEvent = $this->event;
            $iSurveyId = $oEvent->get('surveyId');
            if(!tableExists("{{tokens_{$iSurveyId}}}"))
                return;
            self::__init();
            $bUse=$this->get('bUse', 'Survey', $iSurveyId);
            if(is_null($bUse))
                $bUse=$this->bUse;
            if(!$bUse)
                return;
            //Deactivate for preview
            if(Yii::app()->request->getQuery('action')=='previewgroup' || Yii::app()->request->getQuery('action')=='previewgroup')
                return;

            $sToken= Yii::app()->request->getParam('token');
            // Test and add error for invalid token
            $oToken=TokenDynamic::model($iSurveyId)->find('token=:token',array(':token'=>$sToken));

            if($iSurveyId && !$oToken)
            {
                // Get the survey model
                $oSurvey=Survey::model()->find("sid=:sid",array(':sid'=>$iSurveyId));
                if($oSurvey && $oSurvey->allowregister=="Y" && tableExists("tokens_{$iSurveyId}"))
                {
                    // Fill parameters
                    $bShowTokenForm=$this->get('bShowTokenForm', 'Survey', $iSurveyId,$this->bShowTokenForm);
                    $bConfirmEmail=$this->get('bConfirmEmail', 'Survey', $iSurveyId,$this->bConfirmEmail);

                    // We can go
                    $sLanguage = Yii::app()->request->getParam('lang','');
                    if ($sLanguage=="" )
                    {
                        $sLanguage = Survey::model()->findByPk($iSurveyId)->language;
                    }
                    $aSurveyInfo=getSurveyInfo($iSurveyId,$sLanguage);
                    $sDateAttribute=$this->get('sAttributeDate', 'Survey', $iSurveyId);
                    if($sDateAttribute)
                    {
                        $sDateMin=($this->get('sAttributeDateMin', 'Survey', $iSurveyId)) ? $this->get('sAttributeDateMin', 'Survey', $iSurveyId) : "100 years ago";
                        $sDateMax=($this->get('sAttributeDateMax', 'Survey', $iSurveyId)) ? $this->get('sAttributeDateMax', 'Survey', $iSurveyId) : "now";


                        $sDateMin=date("Y-m-d",strtotime($sDateMin));
                        $sDateMax=date("Y-m-d",strtotime($sDateMax));

                        App()->getClientScript()->registerPackage('jqueryui-timepicker');
                        if (App()->language !== 'en')
                        {
                            Yii::app()->getClientScript()->registerScriptFile(App()->getConfig('third_party')."/jqueryui/development-bundle/ui/i18n/jquery.ui.datepicker-".App()->language.".js");
                        }
                        Yii::app()->getClientScript()->registerScriptFile(Yii::app()->getConfig('publicurl')."plugins/replaceRegister/assets/registerdate.js");
                        $aDateFormat=getDateFormatData($aSurveyInfo['surveyls_dateformat'],$sLanguage);
                        $aOptions=array(
                            'jsdate'=>$aDateFormat['jsdate'],
                            'language'=>$sLanguage,
                            'mindate'=>$sDateMin,
                            'maxdate'=>$sDateMax,
                        );
                        $doRegsiterDateScript="doRegisterDate('register_{$sDateAttribute}',".json_encode($aOptions).")";
                        Yii::app()->getClientScript()->registerScript('doRegsiterDateScript',$doRegsiterDateScript,CClientScript::POS_READY);
                    }
                    Yii::app()->getClientScript()->registerCssFile(Yii::app()->getConfig('publicurl')."plugins/replaceRegister/css/register.css");
                    $sAction= Yii::app()->request->getParam('action','view') ;
                    $sHtmlRegistererror="";
                    $sHtmlRegistermessage1=gT("You must be registered to complete this survey");
                    $sHtmlRegistermessage2=trim($this->get('sDescriptionText', 'Survey', $iSurveyId,""));
                    if(empty($sHtmlRegistermessage2))
                        $sHtmlRegistermessage2=gT("You may register for this survey if you wish to take part.")."<br />\n".gT("Enter your details below, and an email containing the link to participate in this survey will be sent immediately.");
                    $sHtmlRegisterform="";
                    $sHtml="";
                    $bShowForm=true;
                    $bValidMail=false;
                    $bTokenCreate=true;
                    $aExtraParams=array();
                    $aRegisterError=array();
                    $sR_email= Yii::app()->request->getPost('register_email');
                    $sR_emailcontrol= Yii::app()->request->getPost('register_emailcontrol');
                    $sR_firstname= sanitize_xss_string(Yii::app()->request->getPost('register_firstname',""));
                    $sR_lastname= sanitize_xss_string(Yii::app()->request->getPost('register_lastname',""));
                    $aR_attribute=array();
                    $aR_attributeGet=array();
                    $aExtraParams=array();
                    $aMail=array();
                    if($sToken)
                        $aRegisterError[]=gt("This is a controlled survey. You need a valid token to participate.");
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
                                $sR_email=sanitize_xss_string($sR_email);
                                $aRegisterError[]= gT("The email you used is not valid. Please try again.");
                            }
                            elseif($bConfirmEmail && $sR_email!=$sR_emailcontrol)
                            {
                                $sR_emailcontrol=sanitize_xss_string($sR_emailcontrol);
                                $aRegisterError[]= gT("The email was not the same.");
                            }
                        }
                        elseif($this->get('bEmailMandatroy', 'Survey', $iSurveyId,1))
                        {
                            $aRegisterError[]= sprintf(gT("%s cannot be left empty").".", gt("Email address"));
                        }
                        // Add firstname and lmast name if settings ask it
                        if($this->get('bFirstnameMandatroy', 'Survey', $iSurveyId,0) && $sR_firstname=="")
                                $aRegisterError[]= sprintf(gT("%s cannot be left empty").".", gt("First name"));
                        if($this->get('bLastnameMandatroy', 'Survey', $iSurveyId,0) && $sR_firstname=="")
                                $aRegisterError[]= sprintf(gT("%s cannot be left empty").".", gt("Last name"));

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
                            //~ $oToken=TokenDynamic::model($iSurveyId)->find('email=:email',array(':email'=>$sR_email));
                            $aTokenInformations=$this->getTokenInformations($iSurveyId);
                            $oCriteria = new CDbCriteria;
                            foreach($aTokenInformations['aDuplicateControl'] as $sAttribute)
                            {
                                if($sAttribute=='firstname')
                                    $oCriteria->compare('LOWER(firstname)',strtolower($sR_firstname));
                                elseif($sAttribute=='lastname')
                                    $oCriteria->compare('LOWER(lastname)',strtolower($sR_lastname));
                                elseif($sAttribute=='email')
                                    $oCriteria->compare('LOWER(email)',strtolower($sR_email));
                                else
                                    $oCriteria->compare('LOWER('.$sAttribute.')',strtolower($aR_attribute[$sAttribute]));
                            }
                            if($sPrefillWithDate=$this->get('sPrefillWithDate', 'Survey', $iSurveyId))
                            {
                                // Validate if exist
                                $now=dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig('timeadjust'));
                                switch($this->get('sPrefillDateSystem', 'Survey', $iSurveyId,'school'))
                                {
                                    case 'year':
                                        $aR_attributeGet[$sAttributeDate]=dateShift(date("Y-m-d H:i:s"), "Y", Yii::app()->getConfig('timeadjust'));
                                        break;
                                    case 'monthyear':
                                        $aR_attributeGet[$sAttributeDate]=dateShift(date("Y-m-d H:i:s"), "Y-m", Yii::app()->getConfig('timeadjust'));
                                        break;
                                    case 'all':
                                        $aR_attributeGet[$sAttributeDate]=dateShift(date("Y-m-d H:i:s"), "Y-m-d", Yii::app()->getConfig('timeadjust'));
                                        break;
                                    case 'school':
                                    default:
                                        $now=dateShift(date("Y-m-d H:i:s"), "Y-m-d", Yii::app()->getConfig('timeadjust'));
                                        $aNow=explode("-",$now);
                                        if(intval($aNow[1])<7)
                                        {
                                            $nextJuly=date("Y", strtotime($aNow[0]."-07-".$aNow[2]));
                                            $aNow[0]--;
                                            $lastJuly=date("Y", strtotime($aNow[0]."-07-".$aNow[2]));
                                        }
                                        else
                                        {
                                            $lastJuly=date("Y", strtotime($aNow[0]."-07-".$aNow[2]));
                                            $aNow[0]++;
                                            $nextJuly=date("Y", strtotime($aNow[0]."-07-".$aNow[2]));
                                        }
                                        $aR_attributeGet[$sPrefillWithDate]=$lastJuly.$nextJuly;
                                }
                                if($this->get('sPrefillDateDuplicate', 'Survey', $iSurveyId,1))
                                {
                                    $oCriteria->compare($sPrefillWithDate,$aR_attributeGet[$sPrefillWithDate]);
                                }
                            }
                            $oToken=TokenDynamic::model($iSurveyId)->find($oCriteria);
                            if ($oToken)
                            {
                                if($oToken->email!="")
                                {
                                    if($oToken->usesleft<1 && $aSurveyInfo['alloweditaftercompletion']!='Y')
                                    {
                                        $sRegisterError=trim($this->get('sErrorCompleted', 'Survey', $iSurveyId,""));
                                        if(empty($sRegisterError))
                                            $sRegisterError="The email address you have entered is already registered an the questionnaire has been completed.";
                                        $aRegisterError[]=$sRegisterError;
                                    }
                                    elseif(strtolower(substr(trim($oToken->emailstatus),0,6))==="optout")// And global blacklisting ?
                                    {
                                        $sRegisterError=trim($this->get('sErrorOptoput', 'Survey', $iSurveyId,""));
                                        if(empty($sRegisterError))
                                            $sRegisterError="This email address is already registered but someone ask to don't receive new email again.";
                                        $aRegisterError[]=$sRegisterError;
                                    }
                                    elseif($oToken->emailstatus!="OK")
                                    {
                                        $sRegisterError=trim($this->get('sErrorBounced', 'Survey', $iSurveyId,""));
                                        if(empty($sRegisterError))
                                            $sRegisterError="This email address is already registered but the email adress was bounced.";
                                        $aRegisterError[]=$sRegisterError;
                                    }
                                    else
                                    {
                                        $iTokenId=$oToken->tid;
                                        $sEmailTemplate=$this->get('sEmailTemplate', 'Survey', $iSurveyId,$this->sEmailTemplate);
                                        if($sEmailTemplate!="none")
                                        {
                                            if(isset($aSurveyInfo['surveyls_email_'.$sEmailTemplate.'_subj']))
                                            {
                                                $aMail['subject']=$aSurveyInfo['surveyls_email_'.$sEmailTemplate.'_subj'];
                                                $aMail['message']=$aSurveyInfo['surveyls_email_'.$sEmailTemplate];
                                            }
                                            else
                                            {
                                                $aMail['subject']=$aSurveyInfo['email_register_subj'];
                                                $aMail['message']=$aSurveyInfo['email_register'];
                                            }
                                            $sInformation=trim($this->get('sSuccessRegistred', 'Survey', $iSurveyId,""));
                                            if(empty($sInformation))
                                                $sInformation="The address you have entered is already registered. An email has been sent to this address with a link that gives you access to the survey.";
                                            $aMail['information']=$sInformation;
                                            // Did we update the token ? Setting ?
                                        }
                                        $sRegisterError=trim($this->get('sSuccessRegistred', 'Survey', $iSurveyId,""));
                                        if(empty($sRegisterError))
                                            $sRegisterError="The address you have entered is already registered.";
                                        $aRegisterError[]=$sRegisterError;
                                    }
                                }
                                else
                                {
                                    $sRegisterError=trim($this->get('sErrorNoemail', 'Survey', $iSurveyId,""));
                                    if(empty($sRegisterError))
                                        $sRegisterError="The information you have entered is already registered but no email is set.";
                                    $aRegisterError[]=$sRegisterError;
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
                                $bNewToken=true;
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
                        if (SendEmailMessage($aMail['message'], $aMail['subject'], $oToken->email, $from, $sitename,$useHtmlEmail,getBounceEmail($iSurveyId)))
                        {
                            // TLR change to put date into sent
                            $today = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig('timeadjust'));
                            $oToken->sent=$today;
                            $oToken->save();
                            $sReturnHtml="<div id='wrapper' class='message tokenmessage'>"
                                . "<p>".gT("Thank you for registering to participate in this survey.")."</p>\n"
                                . "<p>".$aMail['information']."</p>\n"
                                . "<p> {ADMINNAME} ({ADMINEMAIL})</p>"
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
                        if(isset($bNewToken) && $this->get('bRedirectWithout', 'Survey', $iSurveyId,0))
                        {
                            header("Location: ".$surveylink);
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
                        $aExtraParams['sid']=$iSurveyId;
                        $aExtraParams['action']='register';
                        $aExtraParams['lang']=$sLanguage;
                        $sHtmlRegisterform = CHtml::form(Yii::app()->createUrl("/survey/index",$aExtraParams), 'post');
                        $sHtmlRegisterform.="<table class='register'><tbody>\n";
                        $bIsRequired=(bool) ($this->get('bFirstnameMandatroy', 'Survey', $iSurveyId,0));
                        $sHtmlRegisterform.=  "<tr".( $bIsRequired ? " class='mandatory'" : '')."><th><label for='register_firstname'>".gT("First name") . "</label></th><td>".CHtml::textField('register_firstname',htmlentities($sR_firstname, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        $sHtmlRegisterform.=  "<tr".( $bIsRequired ? " class='mandatory'" : '')."><th><label for='register_lastname'>".gT("Last name") . "</label></th><td>".CHtml::textField('register_lastname',htmlentities($sR_lastname, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        // Extra attribute
                        foreach ($aSurveyInfo['attributedescriptions'] as $field => $aAttribute)
                        {
                            if (!empty($aAttribute['show_register']) && $aAttribute['show_register'] == 'Y')
                            {
                                $bIsRequired=($aAttribute['mandatory'] == 'Y');
                                $sHtmlRegisterform.=  "<tr".( $bIsRequired ? " class='mandatory'" : '')."><th><label for='register_{$field}'>".$aSurveyInfo['attributecaptions'][$field]."</label></th><td>".CHtml::textField('register_'.$field,htmlentities($aR_attribute[$field], ENT_QUOTES, 'UTF-8'),array('class'=>'text','required'=>$bIsRequired))."</td></tr>\n";
                            }
                        }
                        $sHtmlRegisterform.=  "<tr class='mandatory'><th><label for='register_email'>".gT("Email address") . "</label></th><td>".CHtml::textField('register_email',htmlentities($sR_email, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";
                        if($bConfirmEmail)
                            $sHtmlRegisterform.=  "<tr class='mandatory'><th><label for='register_emailcontrol'>".gT("Confirmation") . "</label></th><td>".CHtml::textField('register_emailcontrol',htmlentities($sR_emailcontrol, ENT_QUOTES, 'UTF-8'),array('class'=>'text'))."</td></tr>\n";


                        if (function_exists("ImageCreate") && isCaptchaEnabled('registrationscreen', $aSurveyInfo['usecaptcha']))
                            $sHtmlRegisterform.= "<tr><th><label for='loadsecurity'>" . gT("Security question") . "</label></th><td><img src='".Yii::app()->getController()->createUrl("/verification/image/sid/{$iSurveyId}")."' alt='' /><input type='text' size='5' maxlength='3' name='loadsecurity' id='loadsecurity' value='' /></td></tr>\n";
                        $sHtmlRegisterform.= "<tr><td></td><td>".CHtml::submitButton(gT("Continue"))."</td></tr>";
                        $sHtmlRegisterform.= "</tbody></table>\n";
                        $sHtmlRegisterform.= makeLanguageChangerSurvey($sLanguage);// Need to be inside the form
                        $sHtmlRegisterform.= CHtml::endForm();

                        if($bShowTokenForm)
                        {
                            unset($aExtraParams['action']);
                            $aExtraParams['lang']=$sLanguage;
                            $sHtmlTokenform = CHtml::form(Yii::app()->createUrl("/survey/index",$aExtraParams), 'post',array('id'=>'tokenform'));
                            $sTokenMessage=trim($this->get('sDescriptionToken', 'Survey', $iSurveyId,""));
                            if(empty($sTokenMessage))
                                $sTokenMessage=gT("If you have been issued a token, please enter it in the box below and click continue.");
                            $sHtmlTokenform .= CHtml::tag("p",array("id"=>'tokenmessage'),$sTokenMessage);
                            $sHtmlTokenform .= CHtml::openTag("ul");
                            $sHtmlTokenform .= CHtml::tag("li",array(), CHtml::label(gT("Token:"),"token").CHtml::passwordField("token","",array("id"=>"token",'required'=>true)));
                            if (function_exists("ImageCreate") && isCaptchaEnabled('surveyaccessscreen', $aSurveyInfo['usecaptcha']))
                            {
                                $sHtmlRegisterform .="<li>
                                <label for='captchaimage'>".gT("Security Question")."</label><img id='captchaimage' src='".Yii::app()->getController()->createUrl('/verification/image/sid/'.$surveyid)."' alt='captcha' /><input type='text' size='5' maxlength='3' name='loadsecurity' value='' />
                                </li>";
                            }
                            $sHtmlTokenform .= CHtml::tag("li",array(), CHtml::hiddenField('sid',$iSurveyId).CHtml::hiddenField('newtest',"Y").CHtml::htmlButton(gT("Continue"),array("type"=>'submit','class'=>"submit button")));
                            $sHtmlTokenform.= CHtml::endForm();
                        }
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
                    if(!empty($sHtmlTokenform))
                        echo CHtml::tag("div",array("id"=>'wrapper',"class"=>'wrapper-token form'),$sHtmlTokenform);
                    echo templatereplace(file_get_contents($sTemplatePath.'/endpage.pstpl'),array(), $aData);
                    doFooter();
                    ob_flush();
                    App()->end();
                }
            }
        }
        private function getTokenInformations($iSurveyId)
        {
            $aTokenTableFields = getTokenFieldsAndNames($iSurveyId);
            unset($aTokenTableFields['token']);
            unset($aTokenTableFields['sent']);
            unset($aTokenTableFields['emailstatus']);
            unset($aTokenTableFields['remindersent']);
            unset($aTokenTableFields['remindercount']);
            unset($aTokenTableFields['usesleft']);

            $aShownAttribute=array();
            $aHiddenAttribute=array();

            foreach($aTokenTableFields as $sAttribute=>$aSettings)
            {
                if((isset($aSettings['showregister']) && $aSettings['showregister']=='Y') || (isset($aSettings['show_register']) && $aSettings['show_register']=='Y'))
                    $aShownAttribute[$sAttribute]=$aSettings['description'];
                else
                    $aHiddenAttribute[$sAttribute]=$aSettings['description'];

                $aAttributeToken[$sAttribute]=$aSettings['description'];
            }
            $sAttributeTokenControl=$this->get('aDuplicateControl', 'Survey', $iSurveyId);
            if(is_null($sAttributeTokenControl))
                $aDuplicateControl=array('email');
            else
            {
                $aDuplicateControl=json_decode($sAttributeTokenControl);
                $aDuplicateControl=array_intersect ($aDuplicateControl,array_keys($aAttributeToken));
            }
            if(empty($aDuplicateControl))
            {
                $aDuplicateControl=array('email');
            }
            $aUsableAttribute=$aShownAttribute;

            unset($aUsableAttribute['firstname']);
            unset($aUsableAttribute['lastname']);
            unset($aUsableAttribute['email']);
            unset($aUsableAttribute['language']);
            unset($aHiddenAttribute['firstname']);
            unset($aHiddenAttribute['lastname']);
            unset($aHiddenAttribute['email']);
            unset($aHiddenAttribute['language']);
            return array(
                'aShownAttribute'=>$aShownAttribute,
                'aUsableAttribute'=>$aUsableAttribute,
                'aHiddenAttribute'=>$aHiddenAttribute,
                'aDuplicateControl'=>$aDuplicateControl,
            );
        }
    }
?>
