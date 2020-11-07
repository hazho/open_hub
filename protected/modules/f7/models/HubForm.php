<?php

use function GuzzleHttp\json_decode;

class HubForm
{
	public static $limitTabularDynamicRow = 100;

	public static function getOrCreateIntake($title, $params = array())
	{
		try {
			$intake = self::getIntakeByTitle($title);
		} catch (Exception $e) {
			$intake = self::createIntake($title, $params);
		}

		return $intake;
	}

	public static function getOrCreateIntakeForm($intakeId, $formTitle, $params = array())
	{
		$return = null;
		$intake = Intake::model()->findByPk($intakeId);
		if (!empty($intake->forms)) {
			foreach ($intake->forms as $form) {
				if ($form->title == $formTitle) {
					$return = $form;
					break;
				}
			}
		}

		if (empty($return)) {
			$return = new Form();
		}

		return $return;
	}

	public static function getOrCreateForm2Intake($intakeId, $formId)
	{
		$form2Intake = Form2Intake::model()->findByAttributes(array('intake_id' => $intakeId, 'form_id' => $formId));
		if (empty($form2Intake)) {
			$return = new Form2Intake();
			$return->intake_id = $intakeId;
			$return->form_id = $formId;
			$return->is_active = 1;
			$return->save();
		} else {
			$return = $form2Intake;
		}

		return $return;
	}

	public static function getIntakeByTitle($title)
	{
		$model = Intake::model()->title2obj($title);
		if ($model === null) {
			throw new CHttpException(404, 'The requested intake does not exist.');
		}

		return $model;
	}

	public static function createIntake($title, $params = array())
	{
		$intake = new Intake();
		$intake->title = $title;
		$intake->save();

		return $intake;
	}

	public static function convertJsonToHtml($isEnabled, $json, $data, $slug, $sid, $realm = 'frontend')
	{
		$htmlBody = '';
		$htmlForm = '';
		$decoded = json_decode($json, true);
		$decodedData = empty($data) ? null : json_decode($data, true);
		$formType = strtolower($decoded['form_type']);
		$jScripts = $decoded['jscripts'];
		unset($decoded['form_type'], $decoded['form_type'], $decoded['builder']);

		$jsTags = '';
		if (!empty($jScripts)) {
			$jsTags = self::getJavaScripts($jScripts);
		}

		foreach ($decoded as $item) {
			$key = $item['tag'];
			$value = $item['prop'];

			$members = null;
			if ($key === 'section') {
				$members = $item['members'];
			}

			$innerElements = null;
			if ($key === 'group') {
				$innerElements = $item['members'];
			}

			$htmlBody .= self::getHtmlTag($isEnabled, $key, $formType, $value, $members, $innerElements, $decodedData, $realm);
		}

		$csrfTokenName = Yii::app()->request->csrfTokenName;
		$csrfToken = Yii::app()->request->csrfToken;
		// $session = Yii::app()->session;
		// $session[$csrfTotkenName] = $csrfToken;

		$actionURL = '';
		if ($isEnabled) {
			$actionURL = Yii::app()->createUrl('/f7/publish/save/', array('slug' => $slug, 'sid' => $sid, 'preset' => Yii::app()->request->getQuery('preset')));
		}

		if ($formType === 'horizontal') {
			if ($isEnabled) {
				$htmlForm .= ' <div style="font-size:0">
						<span id="auto-save-span" style="color:gray">Form saved (except upload files)...</span>
						</div>
						<div class="alert alert-error" id="alert-autosave">
							<span>Form saved (except upload files)...</span>
						</div>';
			}

			$htmlForm .= sprintf('
            <form id="f7-form" class="form-horizontal" action="%s" method="%s" enctype="multipart/form-data">
                <input type="hidden" name="%s" value="%s" />
                %s
                <br />
                <input class=" btn btn-primary" type="submit" name="yt1" value="Submit">
            </form>
              %s
            ', $actionURL, 'POST', $csrfTokenName, $csrfToken, $htmlBody, $jsTags);
		} else {
			if ($isEnabled) {
				$htmlForm .= '<span id="auto-save-span" style="color:gray">Form saved...</span>';
			}

			$htmlForm .= sprintf('
            <form id="f7-form" action="%s" method="%s" enctype="multipart/form-data">
                <input type="hidden" name="%s" value="%s" />
                %s
            </form>
              %s
                ', $actionURL, 'POST', $csrfTokenName, $csrfToken, $htmlBody, $jsTags);
		}

		if (!Yii::app()->user->isGuest) {
			$htmlForm = str_replace('%UserEmail%', HUB::getSessionUsername(), $htmlForm);
		} else {
			$htmlForm = str_replace('%UserEmail%', '', $htmlForm);
		}

		$preset = Yii::app()->request->getQuery('preset');
		if (isset($preset)) {
			foreach ($preset as $presetKey => $presetValue) {
				$formattedKey = sprintf('%%%s%%', $presetKey);
				if (strstr($htmlForm, $formattedKey)) {
					$htmlForm = str_replace($formattedKey, $presetValue, $htmlForm);
				}
			}
		}

		return $htmlForm;
	}

	public static function isUrlExists($url)
	{
		$headers = get_headers($url, 0);

		return stripos($headers[0], '200 OK') ? true : false;
	}

	public static function notifyMaker_user_afterSubmitForm($submission)
	{
		$form = $submission->form;

		$params['formType'] = 'form';
		$params['intakeTitle'] = $form->getIntake()->title;
		$params['submissionTitle'] = !empty($intakeTitle) ? $intakeTitle : $form->title;
		$params['submittedData'] = $submission->renderSimpleFormattedHtml();

		$notifyMaker['title'] = 'You have successfully submitted your form.';
		$notifyMaker['message'] = 'You have successfully submitted your form.';
		$notifyMaker['content'] = Yii::app()->getController()->renderPartial('application.modules.f7.views._email.user_afterSubmitForm', $params, true);

		return $notifyMaker;
	}

	public static function notifyMaker_user_afterSubmitDraft($submission)
	{
		$form = $submission->form;

		$params['dateExpiredFormatted'] = Html::formatDateTimezone($form->date_close, 'standard', 'standard', '-', $form->timezone);
		$params['url'] = Yii::app()->createAbsoluteUrl('f7/publish/view', array('slug' => $form->slug, 'sid' => $submission->id));

		$notifyMaker['title'] = 'Your application was saved as draft.';
		$notifyMaker['message'] = 'Your application was saved as draft.';
		$notifyMaker['content'] = Yii::app()->getController()->renderPartial('application.modules.f7.views._email.user_afterSubmitDraft', $params, true);

		return $notifyMaker;
	}

	public static function notifyMaker_user_afterChangedSubmit2Draft($submission)
	{
		$form = $submission->form;

		$params['dateExpiredFormatted'] = Html::formatDateTimezone($form->date_close, 'standard', 'standard', '-', $form->timezone);
		$params['url'] = Yii::app()->createAbsoluteUrl('f7/publish/view', array('slug' => $form->slug, 'sid' => $submission->id));

		$notifyMaker['title'] = 'Application status was changed to draft.';
		$notifyMaker['message'] = 'Application status was changed to draft.';
		$notifyMaker['content'] = Yii::app()->getController()->renderPartial('application.modules.f7.views._email.user_afterChangedSubmit2Draft', $params, true);

		return $notifyMaker;
	}

	protected function getHtmlTag($isEnabled = true, $key, $formType, $value, $members, $innerElements, $decodedData, $realm = 'frontend')
	{
		// exiang: daren code caused issues in form with suddenly appended upload component at bottom of page
		// $value = self::switchLanguage($value);

		$htmlTag = null;
		switch ($key) {
			case 'section':
				$htmlTag = self::getSectionTag($isEnabled, $formType, $value, $members, $innerElements, $decodedData, $realm);
				break;
			case 'group':
				$htmlTag = self::getGroupTag($isEnabled, $formType, $value, $members, $innerElements, $decodedData, $realm);
				break;
			case 'label':
				$htmlTag = self::getLabelTag($value, $formType);
				break;
			case 'headline':
				$htmlTag = self::getHeadlineTag($value, $realm);
				break;
			case 'html':
				$htmlTag = self::getCustomHtmlTag($value, $realm);
				break;
			case 'break':
				$htmlTag = self::getBreakTag($value, $realm);
				break;
			case 'divider':
				$htmlTag = self::getDividerTag($value, $realm);
				break;
			case 'button':
				$htmlTag = self::getButtonTag($isEnabled, $value);
				break;
			case 'googleplace':
				$htmlTag = self::getGooglePlaceTag($isEnabled, $value, $decodedData);
				break;
			case 'url':
				$htmlTag = self::getUrlTag($isEnabled, $value, $decodedData);
				break;
			case 'email':
				$htmlTag = self::getEmailTag($isEnabled, $value, $decodedData);
				break;
			case 'phone':
				$htmlTag = self::getPhoneTag($isEnabled, $value, $decodedData);
				break;
			case 'textbox':
				$htmlTag = self::getTextboxTag($isEnabled, $value, $decodedData);
				break;
			case 'number':
				$htmlTag = self::getNumberTag($isEnabled, $value, $decodedData);
				break;
			case 'textarea':
				$htmlTag = self::getTextareaTag($isEnabled, $value, $decodedData);
				break;
			case 'list':
				$htmlTag = self::getListTag($isEnabled, $value, $decodedData, $realm);
				break;
			case 'checkbox':
				$htmlTag = self::getCheckboxTag($isEnabled, $value, $decodedData);
				break;
			case 'radio':
				$htmlTag = self::getRadiobuttonTag($isEnabled, $value, $decodedData);
				break;
			case 'booleanButton':
				$htmlTag = self::getBooleanButtonTag($isEnabled, $value, $decodedData);
				break;
			case preg_match('/upload.*/', $key) ? true : false:
				$htmlTag = self::getUploadTag($isEnabled, $value, $decodedData);
				break;
			case 'rating':
				$htmlTag = self::getRatingTag($isEnabled, $value, $decodedData);
				break;
			case 'tabular':
				$htmlTag = self::getTabularTag($isEnabled, $formType, $value, $decodedData, $realm);
				break;
			default:
				throw new Exception('Item is not supported: ' . $key);
				break;
		}

		return $htmlTag;
	}

	protected function getJavaScripts($jScripts)
	{
		$tags = self::getJsTags($jScripts);

		$jsTags = sprintf('
      <script>
      $( document ).ready(function() {
        %s
      }); 
      </script>
    ', $tags);

		return $jsTags;
	}

	protected function getJsTags($jsObjects)
	{
		$tags = '';

		foreach ($jsObjects as $jsObj) {
			$caller = $jsObj['caller'];
			$action = $jsObj['action'];
			$items = $jsObj['items'];
			$condition = $jsObj['condition'];

			switch (key($condition)) {
				case 'select':
					$tags .= self::getJsTagForSelectCondition($caller, $condition['select'], $action, $items);
					break;
				case 'check':
					$tags .= self::getJsTagForCheckCondition($caller, $condition['check'], $action, $items);
					break;
				case 'radio':
					$tags .= self::getJsTagForRadioCondition($caller, $condition['radio'], $action, $items);
					break;
				default:
					throw new Exception('condition is not supported.');
			}
		}

		return $tags;
	}

	//Most probably this is a dropdown list
	protected function getJsTagForSelectCondition($caller, $condition, $action, $items)
	{
		$content = '';
		$negate = '';
		foreach ($items as $item) {
			if ($action === 'disable') {
				$content .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
			} elseif ($action === 'enable') {
				$content .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
			} elseif ($action === 'hide') {
				$content .= sprintf('
                $( "#%s" ).hide();
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).show();
                ', $item);
			} elseif ($action === 'show') {
				$content .= sprintf('
                $( "#%s" ).show();
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).hide();
                ', $item);
			}
		}

		$tag = sprintf('
        %s
        $( "#%s" ).change(function() {
        if ($("#%s option:selected").text() == \'%s\')
        {
          %s
        }
        else
        {
          %s
        }
      });
    ', $negate, $caller, $caller, $condition, $content, $negate);

		return $tag;
	}

	//Most probably this event is for a radio/checkbox
	protected function getJsTagForCheckCondition($caller, $condition, $action, $items)
	{
		$content = '';
		$negate = '';
		foreach ($items as $item) {
			if ($action === 'disable') {
				$content .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
			} elseif ($action === 'enable') {
				$content .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
			} elseif ($action === 'hide') {
				$content .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").hide();
                ', $item);
				$negate .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").show();
                ', $item);
			} elseif ($action === 'show') {
				$content .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").show();
                ', $item);
				$negate .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").hide();
                ', $item);
			}
		}
		$tag = '';
		// if ($condition === "No")
		// {

		$tag = sprintf('
          
            %s
          $( "input:checkbox[name=\"%s\"]" ).change(function() {
            var checked = $("input:checkbox[name=\"%s\"]:checked").val();
            
            if(checked == "%s") 
            {
              %s
            }
            else
            {
              %s
            }
          });
      ', $negate, $caller, $caller, $condition, $content, $negate);

		return $tag;
	}

	protected function getJsTagForRadioCondition($caller, $condition, $action, $items)
	{
		$content = '';
		$negate = '';
		foreach ($items as $item) {
			if ($action === 'disable') {
				$content .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
			} elseif ($action === 'enable') {
				$content .= sprintf('
                $( "#%s" ).removeAttr(\'disabled\');
                ', $item);
				$negate .= sprintf('
                $( "#%s" ).attr(\'disabled\',\'disabled\');
                ', $item);
			} elseif ($action === 'hide') {
				$content .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").hide();
                ', $item);
				$negate .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").show();
                ', $item);
			} elseif ($action === 'show') {
				$content .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").show();
                ', $item);
				$negate .= sprintf('
                $( "label[for=\'%s\']" ).closest("div.form-group").hide();
                ', $item);
			}
		}
		$tag = '';
		// if ($condition === "No")
		// {

		$tag = sprintf('  
        %s
        $( "input:radio[name=\"%s\"]" ).change(function() {
			var checked = $("input:radio[name=\"%s\"]:checked").val();
			
			if(checked == "%s") 
			{
				%s
			}
			else
			{
				%s
			}
		});
		$( "input:radio[name=\"%s\"]" ).trigger("change");
      ', $negate, $caller, $caller, $condition, $content, $negate, $caller);

		return $tag;
	}

	protected function getGroupTag($isEnabled, $formType, $params, $members, $innerElements, $decodedData, $realm = 'frontend')
	{
		$innerHtml = '';
		foreach ($innerElements as $element) {
			$key = $element['tag'];
			$value = $element['prop'];
			if ($key === 'group') {
				// throw new Exception('We dont support multiple level of groupping!');
				$members = $element['members'];
				$innerElements = $element['members'];
			}

			$innerHtml .= self::getHtmlTag($isEnabled, $key, $formType, $value, $members, $innerElements, $decodedData, $realm);
		}

		$html = sprintf('<div class="form-group margin-bottom-lg %s">%s</div>', self::formatCss($params['css'], $isEnabled), $innerHtml);

		return $html;
	}

	// todo: not functioning right now
	protected function getGooglePlaceTag($isEnabled, $params, $decodedData)
	{
		$value = empty($decodedData[$params['name']]) ? '' : $decodedData[$params['name']];

		$disable = $isEnabled ? '' : 'disabled';

		$html = sprintf('
            <input %s id="%s" name="%s" value="%s" style="%s" class="form-control googleplace %s" placeholder="%s"
               onFocus="geolocate()" type="text">
            </input>
            <input id="googleplace_address_%s" name="googleplace_address_%s" type="hidden" value="">
            <input id="googleplace_latlng_%s" name="googleplace_latlng_%s" type="hidden" value="">
            <input id="googleplace_place_id_%s" name="googleplace_place_id_%s" type="hidden" value="">
      ', $disable, $params['name'], $params['name'], $value, $params['style'], $params['css'], $params['text'], $params['name'], $params['name'], $params['name'], $params['name'], $params['name'], $params['name']);

		return $html;
	}

	protected function getLabelTag($params, $formType = 'horizontal')
	{
		if ($formType === 'horizontal') {
			if ($params['required'] === 1) {
				$html = sprintf('<label style="%s" class="form-label control-label %s" for="%s">%s <font color="red">*</font></label>', $params['style'], $params['css'], $params['for'], $params['value']);
			} else {
				$html = sprintf('<label style="%s" class="form-label control-label %s" for="%s">%s</label>', $params['style'], $params['css'], $params['for'], $params['value']);
			}
		} else {
			if (empty($params['css'])) {
				if ($params['required'] === 1) {
					$html = sprintf('<label style="%s" class="form-label" for="%s">%s <font color="red">*</font></label>', $params['style'], $params['for'], $params['value']);
				} else {
					$html = sprintf('<label style="%s" class="form-label" for="%s">%s</label>', $params['style'], $params['for'], $params['value']);
				}
			} elseif ($params['required'] === 1) {
				$html = sprintf('<label style="%s" class="form-label %s" for="%s">%s <font color="red">*</font></label>', $params['style'], $params['css'], $params['for'], $params['value']);
			} else {
				$html = sprintf('<label style="%s" class="form-label %s" for="%s">%s</label>', $params['style'], $params['css'], $params['for'], $params['value']);
			}
		}

		return $html;
	}

	protected function getHeadlineTag($params, $realm = 'frontend')
	{
		if ($params['required'] === 1) {
			$html = sprintf('<h%s style="%s" class="form-header %s">%s <font color="red">*</font></h%s>', $params['size'], $params['style'], $params['css'], $params['text'], $params['size']);
		} else {
			$html = sprintf('<h%s style="%s" class="form-header %s">%s</h%s>', $params['size'], $params['style'], $params['css'], $params['text'], $params['size']);
		}

		return $html;
	}

	protected function getCustomHtmlTag($params, $realm = 'frontend')
	{
		$html = sprintf('<div>%s</div>', $params['value']);

		return $html;
	}

	protected function getUrlTag($isEnabled, $params, $decodedData)
	{
		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];

		$disable = $isEnabled ? '' : 'disabled';

		$html = sprintf('<div class="input-group">
            <div class="input-group-addon">
                <i class="fa fa-link"></i>
            </div>
            <input %s type="url" style="%s" class="form-control %s" value="%s" id="%s" name="%s" %s placeholder="%s" />
        </div>', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name'], !empty($params['pattern']) ? sprintf('pattern="%s"', $params['pattern']) : '', $params['placeholder']);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getEmailTag($isEnabled, $params, $decodedData)
	{
		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];

		$disable = $isEnabled ? '' : 'disabled';

		$html = sprintf('<div class="input-group">
            <div class="input-group-addon">
                <span class="glyphicon glyphicon-envelope"></span>
            </div>
            <input %s type="email" style="%s" class="form-control %s" value="%s" id="%s" name="%s">
        </div>', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name']);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getPhoneTag($isEnabled, $params, $decodedData)
	{
		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];

		$disable = $isEnabled ? '' : 'disabled';

		$html = sprintf('<div class="input-group">
            <div class="input-group-addon">
                <span class="glyphicon glyphicon-phone"></span>
            </div>
            <input %s type="tel" style="%s" class="form-control %s" value="%s" id="%s" name="%s">
        </div>
        ', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name']);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getBreakTag($params, $decodedData = '', $realm = 'frontend')
	{
		$html = sprintf('<br />');

		return $html;
	}

	protected function getDividerTag($params, $decodedData = '', $realm = 'frontend')
	{
		$html = sprintf('<hr style="%s" class="%s" />', $params['css'], $params['class']);

		return $html;
	}

	protected function getButtonTag($isEnabled, $params)
	{
		if (!$isEnabled) {
			return '';
		}

		$disable = $isEnabled ? '' : 'disabled';
		$btns = '';

		foreach ($params['items'] as $btn) {
			if ($btn['value'] === 'Draft') {
				$btns .= sprintf('<button %s id="submit" name="%s" value="%s" style="%s" class="button-draft btn %s">%s</button>&nbsp;', $disable, $btn['name'], $btn['value'], $btn['style'], $btn['css'], !empty($btn['text']) ? $btn['text'] : $btn['value']);
			} else {
				$btns .= sprintf('<button %s id="submit" name="%s" value="%s" style="%s" class="button-submit btn %s">%s</button>&nbsp;', $disable, $btn['name'], $btn['value'], $btn['style'], $btn['css'], !empty($btn['text']) ? $btn['text'] : $btn['value']);
			}
		}

		$html = sprintf('
            <div class="row %s">
                <div class="col-sm-12 %s">
                  %s
                </div>
            </div>
            ', $params['css1'], $params['css2'], $btns);

		return $html;
	}

	protected function getTextboxTag($isEnabled, $params, $decodedData, $linkText = '')
	{
		if (isset($params['value'])) {
			$value = $params['value'];
		}

		$preset = Yii::app()->request->getQuery('preset');
		if (isset($preset) && isset($preset[$params['name']])) {
			$value = $preset[$params['name']];
		}

		$disable = $isEnabled ? '' : 'disabled';

		if (isset($params['model_mapping'])) {
			$modelClass = $params['model_mapping'][$params['name']];
			$mappedModelValue = self::getMappedModelData($modelClass);
			if (!empty($mappedModelValue)) {
				$value = $mappedModelValue;
			}
		} else {
			if (!empty($decodedData[$params['name']])) {
				$value = $decodedData[$params['name']];
			}
		}

		$html = '';

		$html .= sprintf('<input %s type="text" style="%s" class="form-control %s" value="%s" name="%s" id="%s" placeholder="%s">', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name'], $params['placeholder']);

		if (!empty($linkText)) {
			$modalID = sprintf('%s-Modal', $modelClass);
			$html .= sprintf('
            <a href="" data-toggle="modal" data-target="#%s">%s</a><div id="startup-alert" style="color:red;display:none;">This name is taken. Choose another name.</div>
            <!-- Modal -->
            <div class="modal fade" id="%s" role="dialog">
                <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add Organization</h4>
                    </div>
                    <div class="modal-body">
                        <div id="org-form-modal">
                        <div id="org-alert-modal" style="display:none" class="alert alert-danger">
                            <strong>Error!</strong> Organization name already exists. Please choose a different name.

                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-name-modal">Organization Name</label>
                                <input type="text" class="form-control" id="org-name-modal" name="org-name-modal" placeholder="Enter organization name">
                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-url-modal">Website</label>
                                <input type="text" class="form-control" id="org-url-modal" name="org-url-modal" placeholder="Enter website">
                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-oneliner-modal">Oneliner</label>
                                <input type="text" class="form-control" id="org-oneliner-modal" name="org-oneliner-modal" placeholder="Enter oneliner">
                        </div>
                            <button name="org-button-modal" id="org-button-modal" class="btn btn-default btn-success btn-block">Create</button>
                        </div>
                    </div>
                </div>

                </div>
            </div>
            ', $modalID, $linkText, $modalID);
		}

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getNumberTag($isEnabled, $params, $decodedData, $linkText = '')
	{
		$disable = $isEnabled ? '' : 'disabled';

		$modelClass = $params['model_mapping'][$params['name']];

		$mappedModelValue = self::getMappedModelData($modelClass);

		$value = empty($mappedModelValue) ? empty($decodedData[$params['name']]) ?
		$params['value'] : $decodedData[$params['name']] : $mappedModelValue;
		$html = '';

		$html .= sprintf('<input %s type="number" step="any" style="%s" class="form-control %s" value="%s" name="%s" id="%s" placeholder="%s">', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name'], $params['placeholder']);

		if (!empty($linkText)) {
			$modalID = sprintf('%s-Modal', $modelClass);
			$html .= sprintf('
            <a href="" data-toggle="modal" data-target="#%s">%s</a><div id="startup-alert" style="color:red;display:none;">This name is taken. Choose another name.</div>
            <!-- Modal -->
            <div class="modal fade" id="%s" role="dialog">
                <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add Organization</h4>
                    </div>
                    <div class="modal-body">
                        <div id="org-form-modal">
                        <div id="org-alert-modal" style="display:none" class="alert alert-danger">
                            <strong>Error!</strong> Organization name already exists. Please choose a different name.

                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-name-modal">Company Name</label>
                                <input type="text" class="form-control" id="org-name-modal" name="org-name-modal" placeholder="Enter Company name">
                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-url-modal">Website</label>
                                <input type="text" class="form-control" id="org-url-modal" name="org-url-modal" placeholder="Enter website">
                        </div>
                        <div class="form-group margin-bottom">
                                <label for="form-label org-oneliner-modal">Oneliner</label>
                                <input type="text" class="form-control" id="org-oneliner-modal" name="org-oneliner-modal" placeholder="Enter oneliner">
                        </div>
                            <button name="org-button-modal" id="org-button-modal" class="btn btn-default btn-success btn-block">Create</button>
                        </div>
                    </div>
                </div>

                </div>
            </div>
            ', $modalID, $linkText, $modalID);
		}

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getOrganizationModalForm($isEnabled, $modelClass, $linkText = 'Create', $params = null)
	{
		if (!$isEnabled) {
			return '';
		}

		$modelId = sprintf('%s-Modal', $modelClass);
		$html = Yii::app()->controller->renderPartial('application.modules.f7.views.hubForm._getOrganizationModalForm', array('modelId' => $modelId, 'linkText' => $linkText, 'modifier' => $params['model_mapping']['modifier']), true);

		return $html;
	}

	protected function getTextareaTag($isEnabled, $params, $decodedData)
	{
		$disable = $isEnabled ? '' : 'disabled';

		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];
		$html = '';

		$html .= sprintf('<textarea %s rows="%s" style="%s" class="form-control %s"name="%s" id="%s" placeholder="%s">%s</textarea>', $disable, isset($params['rows']) ? $params['rows'] : 5, $params['style'], $params['css'], $params['name'], $params['name'], $params['placeholder'], $value);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getListTag($isEnabled, $params, $decodedData, $realm = 'frontend')
	{
		$html = '';
		$options = '';
		$disable = $isEnabled ? '' : 'disabled';

		$preset = Yii::app()->request->getQuery('preset');
		if (isset($preset) && isset($preset[$params['name']])) {
			$value = $params['selected'] = $preset[$params['name']];
		}

		if (isset($params['model_mapping'])) {
			$dataClass = $params['model_mapping'][$params['name']];
			$orgs = self::getMappedModelData($dataClass, $params['model_mapping']);

			if ((empty($orgs) || count($orgs) === 0) && strtolower($dataClass) === 'organization') {
				return self::getTextboxTag($isEnabled, $params, $decodedData, 'Create');
			}
		}

		if ($realm == 'backend' && !$isEnabled && strtolower($dataClass) === 'organization') {
			$value = $decodedData[$params['name']];
			$html .= sprintf('<input %s type="text" style="%s" class="form-control %s" value="%s" name="%s" id="%s">', $disable, $params['style'], $params['css'], $value, $params['name'], $params['name']);
		} else {
			$selectedItem = empty($decodedData[$params['name']]) ? $params['selected'] : $decodedData[$params['name']];

			$defaultItem = sprintf('<option value="">%s</option>', $params['text']);

			$options .= $defaultItem;

			if (empty($orgs) || count($orgs) === 0) {
				foreach ($params['items'] as $item) {
					if ($selectedItem === $item['text']) {
						$options .= sprintf('<option value="%s" selected>%s</option>', $item['text'], $item['text']);
					} else {
						$options .= sprintf('<option value="%s">%s</option>', $item['text'], $item['text']);
					}
				}
			} else {
				foreach ($orgs as $item) {
					// $item = ucwords(strtolower($item)); // ys: need to remove this line although might break as it should not be formatted and can break preset
					if ($selectedItem === $item) {
						$options .= sprintf('<option value="%s" selected>%s</option>', $item, $item);
					} else {
						$options .= sprintf('<option value="%s">%s</option>', $item, $item);
					}
				}
			}
			$html = sprintf('<div><select %s data-class="%s" style="%s" class="form-control %s" text="%s" name="%s" id="%s">%s</select></div>', $disable, strtolower($dataClass), $params['style'], $params['css'], $params['text'], $params['name'], $params['name'], $options);

			if (strtolower($dataClass) === 'organization') {
				$html .= self::getOrganizationModalForm($isEnabled, $dataClass, 'or, Create a new one here', $params);
			}
		}

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getCheckboxTag($isEnabled, $params, $decodedData)
	{
		$html = '';
		$disable = $isEnabled ? '' : 'disabled';

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		// not group checkbox
		if ($params['isGroup'] == 0) {
			$checked = 0;

			if (!empty($decodedData[$params['name']])) {
				$checked = 1;
			} else {
				if ($params['checked'] == 0) {
					$checked = 0;
				}
			}

			if ($params['checked'] == 1) {
				$checked = 1;
			}

			$html .= sprintf(
				'<label class="nobold"><input class="selectives %s" type="checkbox" checked="%s" name="%s" value="%s" style="%s" %s /><div class="checkbox-textChunk-r">%s</div></label>',
				$params['css'],
				$checked,
				$params['name'],
				$params['value'],
				$params['style'],
				$disable,
				$params['text']
			);
		} // group checkbox
		elseif ($params['isGroup'] == 1) {
			$htmlCheckboxes = '';
			if ($params['isInlineItems']) {
				$params['css'] .= ' checkbox-inline';
			}

			foreach ($params['items'] as $checkboxItem) {
				if ($checkboxItem['checked'] == 1 || in_array($checkboxItem['text'], $decodedData[$params['name']])) {
					$htmlCheckboxes = $htmlCheckboxes . sprintf('<label class="nobold %s"><input class="selectives" type="checkbox"  checked="checked" name="%s[]" value="%s" %s />%s</label>', $params['css'], $params['name'], $checkboxItem['text'], $disable, $checkboxItem['text']);
				} else {
					$htmlCheckboxes = $htmlCheckboxes . sprintf('<label class="nobold %s"><input class="selectives" type="checkbox"  name="%s[]" value="%s" %s />%s</label>', $params['css'], $params['name'], $checkboxItem['text'], $disable, $checkboxItem['text']);
				}

				if (!$params['isInlineItems']) {
					$htmlCheckboxes .= '<br />';
				}
			}
			$html .= sprintf('<div>%s</div>', $htmlCheckboxes);
		} else {
			//structure is wrong or missing isGroup property.
		}

		return $html;
	}

	protected function getRadiobuttonTag($isEnabled, $params, $decodedData)
	{
		$radioHTML = '';

		$disable = $isEnabled ? '' : 'disabled';

		foreach ($params['items'] as $value) {
			$isItemChecked = $decodedData[$params['name']] === $value['text'] ? true : false;
			$itemId = sprintf('%s-%s-%s', $params['name'], $value['text'], md5($value['text']));

			if ($value['checked'] === 1 || $isItemChecked) {
				$radioHTML .= sprintf('
                    <div class="radio">
                    <input %s name="%s" type="radio" id="%s" value="%s" checked>
                    <label for="%s">%s</label>
                    </div>', $disable, $params['name'], $itemId, $value['text'], $itemId, $value['text']);
			} else {
				$radioHTML .= sprintf('
                    <div class="radio">
                    <input %s name="%s" type="radio" id="%s" value="%s">
                    <label for="%s">%s</label>
                    </div>', $disable, $params['name'], $itemId, $value['text'], $itemId, $value['text']);
			}
		}
		$isItemChecked = $decodedData[$params['name']] === 'other' ? true : false;

		if ($params['other'] === 1) {
			if ($isItemChecked) {
				$radioHTML .= sprintf('
                <div class="radio">
                <input %s name="%s" type="radio" checked="1" id="other-%s" value="%s">
                <label for="other-%s">%s</label>
                </div>
                <input %s name="other-%s" id="other-%s" value="%s" maxlength="40" size="40">', $disable, $params['name'], $params['name'], 'other', $params['name'], 'other', $disable, $params['name'], $params['name'], $decodedData['other-' . $params['name']]);
			} else {
				$radioHTML .= sprintf('
                <div class="radio">
                <input %s name="%s" type="radio" id="other-%s" value="%s">
                <label for="other-%s">%s</label>
                </div>
                <input %s name="other-%s" id="other-%s" value="%s" maxlength="40" size="40">', $disable, $params['name'], $params['name'], 'other', $params['name'], 'other', $disable, $params['name'], $params['name'], $decodedData['other-' . $params['name']]);
			}
		}

		$html = $radioHTML;

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getBooleanButtonTag($isEnabled, $params, $decodedData)
	{
		$radioHTML = '';

		$disable = $isEnabled ? '' : 'disabled';
		$params['items'] = array(
			array('text' => Yii::t('core', 'Yes')),
			array('text' => Yii::t('core', 'No')),
		);

		foreach ($params['items'] as $value) {
			$isItemChecked = $decodedData[$params['name']] === $value['text'] ? true : false;
			$itemId = sprintf('%s-%s-%s', $params['name'], $value['text'], md5($value['text']));

			if ($value['checked'] === 1 || $isItemChecked) {
				$radioHTML .= sprintf('
                    <div class="radio">
                    <input %s name="%s" type="radio" id="%s" value="%s" checked>
                    <label for="%s">%s</label>
                    </div>', $disable, $params['name'], $itemId, $value['text'], $itemId, $value['text']);
			} else {
				$radioHTML .= sprintf('
                    <div class="radio">
                    <input %s name="%s" type="radio" id="%s" value="%s">
                    <label for="%s">%s</label>
                    </div>', $disable, $params['name'], $itemId, $value['text'], $itemId, $value['text']);
			}
		}

		$html = $radioHTML;

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	protected function getSectionTag($isEnabled, $formType, $params, $members, $innerElements, $decodedData, $realm = 'frontend')
	{
		if ($params['mode'] == 'accordion') {
			$html = sprintf('<div class="panel-group margin-bottom-lg %s" id="%s" role="tablist" aria-multiselectable="true" style="%s">', $params['class'], $params['name'], $params['style']);

			$html .= sprintf('<div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="panelHeading-%s">', $params['name']);

			$html .= sprintf('<h4 class="panel-title"><a role="button" data-toggle="collapse" data-parent="#%s" href="#panelCollapse-%s" aria-expanded="true" aria-controls="panelCollapse-%s">%s</a></h4></div>', $params['name'], $params['name'], $params['name'], $params['text']);

			$html .= sprintf('<div id="panelCollapse-%s" class="panel-collapse collapse %s" role="tabpanel" aria-labelledby="panelCollapse-%s">', $params['name'], $params['accordionOpen'], $params['name']);

			$html .= '<div class="panel-body">';
		} else {
			$html = sprintf('<section id="%s" class="%s" style="%s">', $params['name'], $params['class'], $params['style']);
		}

		$htmlBody = '';
		$seen = 0;
		$label = '';
		foreach ($members as $element) {
			$key = $element['tag'];
			$value = $element['prop'];
			$members = null;
			if ($key === 'section') {
				$members = $element['members'];
			} elseif ($key == 'group') {
				// throw new Exception('We dont support multiple level of groupping!');
				$members = $element['members'];
				$innerElements = $element['members'];
			}

			$htmlBody .= self::getHtmlTag($isEnabled, $key, $formType, $value, $members, $innerElements, $decodedData, $realm);
		}

		$html .= $htmlBody;

		if ($params['mode'] == 'accordion') {
			$html .= '</div></div></div></div>';
		} else {
			$html .= '</section>';
		}

		return $html;
	}

	protected function getUploadTag($isEnabled, $params, $decodedData)
	{
		if (is_null($params)) {
			return;
		}
		$disable = $isEnabled ? '' : 'disabled';

		// stupid hardcoded 'uploadfile.aws_path'
		$awsPath = $params['name'] . '.aws_path';
		$value = empty($decodedData[$awsPath]) ? '' : $decodedData[$awsPath];
		$relativeUrl = $value;

		// ys: this should be removed, using session is stupid
		$session = Yii::app()->session;
		if (empty($value) && !empty($session['uploadfiles'][$awsPath])) {
			$value = $session['uploadfiles'][$awsPath];
		}

		if (!empty($value)) {
			$value = join('_', array_slice(explode('_', basename($value)), 1));
		}

		$html = '<div class="form-group">';
		$html .= sprintf('<input %s type="file" name="%s" id="%s" style="%s" class="form-control %s">', $disable, $params['name'], $params['name'], $params['style'], $params['css']);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		if (!empty($value)) {
			$html .= sprintf('<div><p>Attached File:</p><ul><li>
			%s</li></ul></div>', CHtml::link(CHtml::encode($value), Yii::app()->createAbsoluteUrl('/f7/publish/download/', array('filename' => basename($relativeUrl))), array('target' => '_blank')));
		}
		$html .= '</div>';

		return $html;
	}

	public function getRatingTag($isEnabled, $params, $decodedData)
	{
		if (substr($params['name'], 0, strlen('voted-')) != 'voted-') {
			$params['name'] = sprintf('voted-%s', $params['name']);
		}

		$disable = $isEnabled ? '' : 'disabled';
		$seed = explode('-', $params['name'])[1];
		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];
		$html = '';
		$labelLow = 'Strongly Disagree';
		if (isset($params['label_low'])) {
			$labelLow = $params['label_low'];
		}
		$labelHigh = 'Strongly Agree';
		if (isset($params['label_high'])) {
			$labelHigh = $params['label_high'];
		}

		$cells = '';
		if (empty($value)) {
			$cells = sprintf('
            <div id="1-%s" class="rating col-xs-2">1</div>
            <div id="2-%s" class="rating col-xs-2">2</div>
            <div id="3-%s" class="rating col-xs-2">3</div>
            <div id="4-%s" class="rating col-xs-2">4</div>
            <div id="5-%s" style="border-right-width:1.5px;" class="rating col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
		} else {
			if ($value === '1') {
				$cells = sprintf('
                <div id="1-%s" style="background-color:black;color:white" class="rating col-xs-2">1</div>
                <div id="2-%s" class="rating col-xs-2">2</div>
                <div id="3-%s" class="rating col-xs-2">3</div>
                <div id="4-%s" class="rating col-xs-2">4</div>
                <div id="5-%s" style="border-right-width:1.5px;" class="rating col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
			} elseif ($value === '2') {
				$cells = sprintf('
                <div id="1-%s" class="rating col-xs-2">1</div>
                <div id="2-%s" style="background-color:black;color:white" class="rating col-xs-2">2</div>
                <div id="3-%s" class="rating col-xs-2">3</div>
                <div id="4-%s" class="rating col-xs-2">4</div>
                <div id="5-%s" style="border-right-width:1.5px;" class="rating  col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
			} elseif ($value === '3') {
				$cells = sprintf('
                <div id="1-%s" class="rating col-xs-2">1</div>
                <div id="2-%s" class="rating col-xs-2">2</div>
                <div id="3-%s" style="background-color:black;color:white" class="rating col-xs-2">3</div>
                <div id="4-%s" class="rating col-xs-2">4</div>
                <div id="5-%s" style="border-right-width:1.5px;" class="rating  col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
			} elseif ($value === '4') {
				$cells = sprintf('
                <div id="1-%s" class="rating col-xs-2">1</div>
                <div id="2-%s" class="rating col-xs-2">2</div>
                <div id="3-%s" class="rating col-xs-2">3</div>
                <div id="4-%s" style="background-color:black;color:white" class="rating col-xs-2">4</div>
                <div id="5-%s" style="border-right-width:1.5px;" class="rating  col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
			} elseif ($value === '5') {
				$cells = sprintf('
                <div id="1-%s" class="rating col-xs-2">1</div>
                <div id="2-%s" class="rating col-xs-2">2</div>
                <div id="3-%s" class="rating col-xs-2">3</div>
                <div id="4-%s" class="rating col-xs-2">4</div>
                <div id="5-%s" style="background-color:black;color:white; border-right-width:1.5px;" class="rating col-xs-2">5</div>', $seed, $seed, $seed, $seed, $seed);
			}
		}
		$html .= sprintf('
        <div class="container margin-top-md" style="max-width:600px; margin:0">
            <div class="row"><div id="rating-%s">%s</div></div>
            <div class="row">
                <div class="rating-label nopadding col-xs-6"><small>&larr;%s</small></div>
                <div class="rating-label col-xs-4 nopadding" style="text-align:right;"><small>%s&rarr;</small></div>
            </div>
            <input type="hidden" id="voted-%s" name="voted-%s" value="%s">
        </div>
        
        <script>
        $(\'#rating-%s\').click(function(e) {
            var num = $(\'#\' + e.target.id).text();
            if( num == "1" || num == "2" || num == "3" || num == "4" || num == "5")
            {
                $(this).children("div").each(function (x,y) {
                    if (e.target.id != y.id)
                    {
                        $(\'#\' + y.id).css("background-color", "white");
                        $(\'#\' + y.id).css("color", "black");
                    }
                });
                
				$(\'#voted-%s\').val(num);
				$(\'#\' + e.target.id).css("background-color", "black");
				$(\'#\' + e.target.id).css("color", "white");
            }
            });
        </script>', $seed, $cells, $labelLow, $labelHigh, $seed, $seed, $value, $seed, $seed);

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}

		return $html;
	}

	public function getTabularTag($isEnabled, $formType, $params, $decodedData, $realm)
	{
		$disable = $isEnabled ? '' : 'disabled';
		$seed = explode('-', $params['name'])[1];
		$value = empty($decodedData[$params['name']]) ? $params['value'] : $decodedData[$params['name']];
		$isDynamicRow = self::isTabularWithDRow($params);
		$limitTabularDynamicRow = self::$limitTabularDynamicRow;
		if (!empty($params['limitTabularDynamicRow'])) {
			$limitTabularDynamicRow = $params['limitTabularDynamicRow'];
		}

		$html = '';

		if (!empty($params['hint'])) {
			$html .= sprintf('<span class="help-block"><small>%s</small></span>', $params['hint']);
		}
		$html .= sprintf('<table class="table table-bordered" id="tabular-%s">', $params['name']);
		$html .= '<thead><tr>';
		for ($i = 0; $i < count($params['headers']); $i++) {
			$html .= sprintf('<th class="%s">', $params['headers'][$i]['css']);
			$html .= $params['headers'][$i]['text'];
			if ($params['headers'][$i]['required'] == 1) {
				$html .= '<font color="red">*</font>';
			}
			if (isset($params['headers'][$i]['hint'])) {
				$html .= sprintf('<div class="text-normal" style="font-weight:normal"><small>%s</small></div>', $params['headers'][$i]['hint']);
			}
			$html .= '</th>';
		}
		// for last action column: trash in dynamic row
		if ($isEnabled && $isDynamicRow) {
			$html .= '<th></th>';
		}

		$html .= '</tr></thead>';
		$html .= '<tbody>';
		for ($i = 0; $i < count($params['members']); $i++) {
			$html .= '<tr>';
			for ($j = 0; $j < count($params['headers']); $j++) {
				$html .= '<td>';

				$item = $params['members'][$i]['members'][$j];
				$key = $item['tag'];
				$itemParams = $item['prop'];

				switch ($key) {
					case 'label':
						$htmlTag = self::getLabelTag($itemParams, $formType);
						break;
					case 'url':
						$htmlTag = self::getUrlTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'email':
						$htmlTag = self::getEmailTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'phone':
						$htmlTag = self::getPhoneTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'textbox':
						$htmlTag = self::getTextboxTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'number':
						$htmlTag = self::getNumberTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'textarea':
						$htmlTag = self::getTextareaTag($isEnabled, $itemParams, $decodedData);
						break;
					case 'list':
						$htmlTag = self::getListTag($isEnabled, $itemParams, $decodedData, $realm);
						break;
					case 'booleanButton':
						$htmlTag = self::getBooleanButtonTag($isEnabled, $itemParams, $decodedData);
						break;
					default:
						throw new Exception('Item is not supported in tabular field');
						break;
				}
				$html .= $htmlTag;
				$html .= '</td>';
			}

			if ($isDynamicRow && $isEnabled) {
				$html .= sprintf('<td class="text-center"><a class="btn btn-md btn-white btn-deleteRow">%s</a></td>', Html::faIcon('fa-trash', array('class' => '')));
			}

			$html .= '</tr>';
		}

		if ($isDynamicRow) {
			// todo: currently `k` is hard code to max limit row `limitTabularDynamicRow` x 2 for the list size to process to find submitted value of a dynamic tabular field. It's possible that some submitted row data got truncated, especially when user deleted and added row too frequent. a proper way is to look thru the json_data with regex and find the largest count of value eg: shareholders7-amountInvested -> 7
			for ($k = 1; $k <= $limitTabularDynamicRow * 2; $k++) {
				if (!self::isTabularTagDRowFilled($k, $params, $decodedData)) {
					continue;
				}
				$html .= sprintf('<tr class="drow" data-index="%s">', $k);
				for ($j = 0; $j < count($params['headers']); $j++) {
					$item = $params['members'][0]['members'][$j];
					$item['prop']['name'] = str_replace('%%N%%', $k, $item['prop']['name']);
					$item['prop']['value'] = str_replace('%%N%%', $k, $item['prop']['value']);
					$item['prop']['hint'] = str_replace('%%N%%', $k, $item['prop']['hint']);
					$key = $item['tag'];
					$itemParams = $item['prop'];

					$varName = sprintf('%s%s-%s', $params['name'], $k, $item['prop']['name']);

					$htmlTag = $varName;

					switch ($key) {
						case 'label':
							$htmlTag = self::getLabelTag($itemParams, $formType);
							break;
						case 'url':
							$htmlTag = self::getUrlTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'email':
							$htmlTag = self::getEmailTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'phone':
							$htmlTag = self::getPhoneTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'textbox':
							$htmlTag = self::getTextboxTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'number':
							$htmlTag = self::getNumberTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'textarea':
							$htmlTag = self::getTextareaTag($isEnabled, $itemParams, $decodedData);
							break;
						case 'list':
							$htmlTag = self::getListTag($isEnabled, $itemParams, $decodedData, $realm);
							break;
						case 'booleanButton':
							$htmlTag = self::getBooleanButtonTag($isEnabled, $itemParams, $decodedData);
							break;
						default:
							throw new Exception('Item is not supported in tabular field');
							break;
					}
					$html .= sprintf('<td>%s</td>', $htmlTag);
				}
				if ($isEnabled) {
					$html .= sprintf('<td class="text-center"><a class="btn btn-md btn-white btn-deleteRow">%s</a></td>', Html::faIcon('fa-trash', array('class' => '')));
				}

				$html .= '</tr>';
			}

			if ($isEnabled) {
				$html .= sprintf('<tr class="text-right"><td colspan="%s"><a class="btn btn-xs btn-success btn-addRow">%s %s</a></td></tr>', $j + 1, Html::faIcon('fa-plus'), Yii::t('f7', 'Add Row'));
			}
		}

		$html .= '</tbody>';
		$html .= '</table>';

		if ($isDynamicRow) {
			$html .= sprintf('<script>
			$( document ).ready(function() {
				$("#tabular-%s tbody").children("tr:first").addClass("drow").attr("data-index", 0).hide();

				$("#tabular-%s .btn-addRow").click(function(e){
					var newRowIndex = parseInt($("#tabular-%s .drow:last").data("index"))+1;
					var totalRow = $(this).closest("tbody").children(".drow").length-1;
					if(totalRow < %s){
						sampleRow = $(this).closest("tbody").children("tr:first").html();
						sampleRow = sampleRow.replace(/%%%%N%%%%/g, newRowIndex);
						$("#tabular-%s tr:last").before("<tr class=\"drow\" data-index=\""+(newRowIndex)+"\">"+sampleRow+"</tr>");
					}
					else
					{
						toastr.options = {
							"closeButton": true,
							"preventDuplicates": true,
							"positionClass" : "toast-top-center",
						};
						toastr.warning("Limit Reached");
					}
				});
				$("#tabular-%s").on("click", ".btn-deleteRow", function(e){
					$(this).closest("tr.drow").remove();
				});

				var totalRow = parseInt($("#tabular-%s .drow:last").data("index"))
				if(totalRow < 1)
				{
					$("#tabular-%s .btn-addRow").trigger("click");
				}
			}); 
			</script>', $params['name'], $params['name'], $params['name'], $limitTabularDynamicRow, $params['name'], $params['name'], $params['name'], $params['name']);
		}

		return $html;
	}

	// check is a specific dynamic row has at least one field filled with value in stored decoded data
	protected static function isTabularTagDRowFilled($count, $params, $decodedData)
	{
		for ($j = 0; $j < count($params['headers']); $j++) {
			$item = $params['members'][0]['members'][$j];
			$item['prop']['name'] = str_replace('%%N%%', $count, $item['prop']['name']);

			if (!empty($item['prop']['name'])) {
				$item['prop']['value'] = str_replace('%%N%%', $count, $item['prop']['value']);
				$item['prop']['hint'] = str_replace('%%N%%', $count, $item['prop']['hint']);
				$key = $item['tag'];
				$itemParams = $item['prop'];

				if (isset($decodedData[$item['prop']['name']])) {
					return true;
				}
			}
		}

		return false;
	}

	// check is the tabular field is a dynamic row type
	protected static function isTabularWithDRow($params)
	{
		for ($i = 0; $i < count($params['members']); $i++) {
			if ($params['members'][$i]['tag'] == 'drow') {
				return true;
			}
		}

		return false;
	}

	public function validateForm($jsonForm, $postedData)
	{
		$errors = array();

		$formObjects = json_decode($jsonForm, true);
		$jScripts = $formObjects['jscripts'];

		foreach ($formObjects as $formObject) {
			if ($formObject['tag'] === 'break' || $formObject['tag'] === 'divider' || $formObject['tag'] === 'label' || $formObject['tag'] === 'headline' || $formObject['tag'] === 'YII_CSRF_TOKEN' || $formObject['tag'] === 'button') {
				continue;
			}

			if ($formObject['tag'] === 'section') {
				$members = $formObject['members'];
				foreach ($members as $member) {
					if ($member['tag'] == 'tabular') {
						$errors = array_merge($errors, self::validateTabular($member, $postedData, $jScripts));
					} else {
						if (array_key_exists('required', $member['prop']) && $member['prop']['required'] === 1) {
							$error = self::validateComponent($member['tag'], $member['prop']['name'], $member['prop']['error'], $member['prop']['validation'], $postedData, $jScripts, $member['prop']['csv_label']);

							if (!empty($error)) {
								$errors[] = $error;
							}
						}
					}
				}
			} elseif ($formObject['tag'] === 'group') {
				$members = $formObject['members'];
				foreach ($members as $member) {
					if ($member['tag'] == 'tabular') {
						$errors = array_merge($errors, self::validateTabular($member, $postedData, $jScripts));
					} else {
						if (array_key_exists('required', $member['prop']) && $member['prop']['required'] === 1) {
							$error = self::validateComponent($member['tag'], $member['prop']['name'], $member['prop']['error'], $member['prop']['validation'], $postedData, $jScripts, $member['prop']['csv_label']);

							if (!empty($error)) {
								$errors[] = $error;
							}
						}
					}
				}
			} else {
				if ($formObject['tag'] == 'tabular') {
					$errors = array_merge($errors, self::validateTabular($formObject, $postedData, $jScripts));
				} else {
					if (array_key_exists('required', $formObject['prop']) && $formObject['prop']['required'] === 1) {
						$error = self::validateComponent($formObject['tag'], $formObject['prop']['name'], $formObject['prop']['error'], $formObject['prop']['validation'], $postedData, $jScripts);

						if (!empty($error)) {
							$errors[] = $error;
						}
					}
				}
			}
		}

		return array(empty($errors), $errors);
	}

	protected static function validateTabular($member, $postedData, $jScripts)
	{
		foreach ($member['prop']['members'] as $tabularRow) {
			foreach ($tabularRow['members'] as $member) {
				if ($member['tag'] !== 'label' && array_key_exists('required', $member['prop']) && $member['prop']['required'] === 1 && !strstr($member['prop']['name'], '%%N%%')) {
					$error = self::validateComponent($member['tag'], $member['prop']['name'], $member['prop']['error'], $member['prop']['validation'], $postedData, $jScripts, $member['prop']['csv_label']);

					if (!empty($error)) {
						$errors[] = $error;
					}
				}
			}
		}

		return $errors;
	}

	//Either the text of error should be identified in json as error property
	//or we should specify here.
	// exiang: this is stupid, @mohammad should not break label and form element as different element. told him so but never listen. now is hard to get label
	// element: label, headline, upload...
	// value: field name
	// error: preset error message
	// validation: preset validation code, eg: url in textbox field
	// postedData: array of data from POST
	// jScripts:
	// csvLabel: csv label, optional
	protected static function validateComponent($tag, $value, $error, $validation, $postedData, $jScripts, $csvLabel = '')
	{
		if ($tag === 'section' || $tag === 'group' || $tag === 'label' || $tag === 'headline' || $tag === 'break' || $tag === 'divider' || $tag === 'upload') {
			return;
		}

		if (self::isControlHiddenOrDisable($value, $postedData, $jScripts)) {
			return;
		}

		$labelTitle = !empty($csvLabel) ? $csvLabel : $value;

		if ($tag === 'radio') {
			if (empty($postedData[$value])) {
				return empty($error) ? "$labelTitle is required." : $error;
			}
		} elseif ($tag === 'checkbox') {
			if (empty($postedData[$value])) {
				return empty($error) ? sprintf("At least one item must be checked for field '%s'.", $labelTitle) : $error;
			}
		} elseif ($tag === 'email') {
			if (empty($postedData[$value])) {
				return empty($error) ? "$labelTitle is required." : $error;
			}

			if (empty(filter_var($postedData[$value], FILTER_VALIDATE_EMAIL))) {
				return 'Please provide a valid email.';
			}
		} elseif ($tag === 'phone') {
			if (empty($postedData[$value])) {
				return empty($error) ? "$labelTitle is required." : $error;
			}

			if (!is_numeric($postedData[$value])) {
				return 'Contact/Phone number should only consist of digits.';
			}

			if (strlen($postedData[$value]) < 7) {
				return 'Please provide a valid Contact/Phone number (7-15 digits). ';
			}
		} elseif ($tag === 'textbox' && !empty($validation)) {
			if (strtolower($validation) === 'url' && !filter_var($postedData[$value], FILTER_VALIDATE_URL)) {
				return "Please enter a valid URL for the field $labelTitle.";
			}
		}
		/*elseif ($tag === 'upload') {
			if (empty($postedData[$value])) {
				return empty($error) ? "$labelTitle is required." : $error;
			}
		}*/ else {
			if (empty($postedData[$value])) {
				return empty($error) ? "$labelTitle is required." : $error;
			}
		}
	}

	protected static function isControlHiddenOrDisable($value, $postedData, $jScripts)
	{
		foreach ($jScripts as $script) {
			if (in_array($value, $script['items']) && ($script['action'] === 'hide' || $script['action'] === 'disable')) {
				if (key($script['condition']) === 'check' && array_key_exists($script['caller'], $postedData)) {
					return true;
				}

				if (key($script['condition']) === 'select' && $postedData[$script['caller']] === $script['condition']['select']) {
					return true;
				}
			}
		}

		return false;
	}

	public static function getMappedModelData($model, $mappingParams = '')
	{
		if (empty($model)) {
			return '';
		}
		$organizations = array();
		try {
			$organizations = HubOrganization::getUserActiveOrganizations(HUB::getSessionUsername());
		} catch (Exception $e) {
		}

		if (strtolower($model) == 'organization') {
			return array_map(create_function('$o', 'return $o->title;'), $organizations);
		} elseif (strtolower($model) == 'industry') {
			$industries = array_map(create_function('$t', 'return $t[title];'), Industry::model()->isActive()->findAll(array('order' => 'title')));

			if (!$mappingParams['hideOthers']) {
				$industries[] = 'Other (Please State)';
			}

			return $industries;
		} elseif (strtolower($model) == 'sdg') {
			$sdgs = array_map(create_function('$t', 'return $t[title];'), Sdg::model()->isActive()->findAll());

			return $sdgs;
		} elseif (strtolower($model) == 'cluster') {
			$clusters = array_map(create_function('$t', 'return $t[title];'), Cluster::model()->isActive()->findAll(array('order' => 'title ASC')));

			return $clusters;
		} elseif (strtolower($model) == 'persona') {
			$personas = array_map(create_function('$t', 'return $t[title];'), Persona::model()->isActive()->findAll(array('order' => 'title ASC')));

			return $personas;
		} elseif (strtolower($model) == 'startupstage') {
			$startupStages = array_map(create_function('$t', 'return $t[title];'), StartupStage::model()->isActive()->findAll(array('order' => 'ordering ASC')));

			return $startupStages;
		} elseif (strtolower($model) == 'legalform') {
			$legalForms = array_map(create_function('$t', 'return $t[title];'), Legalform::model()->isActive()->findAll(array('order' => 'title ASC')));

			return $legalForms;
		} elseif (strtolower($model) == 'heard') {
			return array(
				'Social Media',
				'Word of Mouth',
				'Printed Ads',
				'Online Media Portal',
				'Our Newsletter',
				'Events organized by us',
				'Email / Newsletter by other organizations',
			);
		} elseif (strtolower($model) == 'gender') {
			return array('Male', 'Female');
		} elseif (strtolower($model) == 'country') {
			$countries = Country::model()->findAll();

			return array_map(create_function('$t', 'return $t->printable_name;'), $countries);
		}
	}

	public static function getRandomCode()
	{
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	public static function storeFile($file, $uploadPath = '')
	{
		$uploadFolderName = 'forms';

		//$storeId = time();

		$saveFileName = basename($file);

		// if using s3 as storage
		if (Yii::app()->params['storageMode'] == 's3') {
			$mimeType = ysUtil::getMimeType($file);
			$pathInAWS = sprintf('uploads/%s/%s', $uploadFolderName, $saveFileName);
			$result = Yii::app()->s3->upload(
				$file,
				$pathInAWS,
				Yii::app()->params['s3Bucket'],
				array(),
				array('Content-Type' => $mimeType)
			);
			if ($result) {
				unlink($file);
			}
		}
		// if using local as storage
		else {
			//$modelFileFile->saveAs(sprintf($uploadPath.DIRECTORY_SEPARATOR.'%s', $saveFileName));
		}

		return $pathInAWS;
	}

	public static function updateJsonForm($jsonData, $elementName, $newValue, $newkey = '')
	{
		$updatedObj = array();
		$obj = json_decode($jsonData, true);
		$obj[$elementName] = $newValue;

		return json_encode($obj);
	}

	public static function getListOfUploadControls($formCode)
	{
		$result = Yii::app()->db->createCommand()
			->select('json_structure')
			->from('form')
			->where('code=:code', array(':code' => $formCode))->queryRow();

		if (count($result) == 0) {
			return array();
		}

		$items = json_decode($result['json_structure'], true);

		$ret = array();

		foreach ($items as $item) {
			if ($item['tag'] === 'upload') {
				$ret[] = $item['prop']['name'];
			} elseif ($item['tag'] === 'group') {
				$members = $item['members'];
				foreach ($members as $member) {
					if ($member['tag'] === 'upload') {
						$ret[] = $member['prop']['name'];
					}
				}
			}
		}

		return $ret;
	}

	public static function getListOfExistingUploadControlsWithValue($jsonData)
	{
		$return = array();
		if (empty($jsonData)) {
			return $return;
		}

		$dataObjArray = json_decode($jsonData, true);
		foreach ($dataObjArray as $key => $value) {
			if (preg_match('/uploadfile\..*/', $key)) {
				$return[$key] = $value;
			}
		}

		return $return;
	}

	public static function isForm2IntakeExists($formId, $intakeId)
	{
		$condition = 'form_id=:formId && intake_id=:intakeId ';
		$params = array(':formId' => $formId, ':intakeId' => $intakeId);

		return Form2Intake::model()->exists($condition, $params);
	}

	public static function canSubmit($formModel, $submissionId)
	{
		if ($formModel->is_multiple === 1) {
			return true;
		} elseif ($formModel->is_multiple === 0) {
			$allSubmissionsForThisForm = FormSubmission::model()->findAllByAttributes(array('form_code' => $formModel->code, 'user_id' => Yii::app()->user->id));

			if (count($allSubmissionsForThisForm) == 0) {
				return true;
			}

			if (empty($submissionId)) {
				return false;
			}
			//we dont accept new submission

			//Check if we are submitting/drafting the same form
			foreach ($allSubmissionsForThisForm as $submission) {
				if ($submission->id !== $submissionId) {
					return false;
				}
			}

			return true;
		} else {
			throw new Exception('Form is not in a correct state.');
		}
	}

	public static function getFormSubmissions($user, $limit = 100)
	{
		$condition = 'user_id=:userId';
		$params = array(':userId' => $user->id);

		return FormSubmission::model()->findAll($condition, $params);
	}

	public static function getFormSubmissionsByOrganization($organization, $limit = 100)
	{
		$sql = sprintf('SELECT *, JSON_EXTRACT(json_data, "$.startup_id") AS startupId, JSON_EXTRACT(json_data, "$.organization_id") AS organizationId FROM `form_submission` WHERE (JSON_EXTRACT(json_data, "$.startup_id")=%s OR JSON_EXTRACT(json_data, "$.organization_id")=%s) GROUP BY id ORDER BY date_modified DESC', $organization->id, $organization->id);

		return FormSubmission::model()->findAllBySql($sql);
	}

	public static function canUserChooseThisOrgization($userEmail, $orgTitleSubmittedByUser)
	{
		if (empty($orgTitleSubmittedByUser)) {
			throw new Exception('Organization title cannot be empty.');
		}

		$org = Organization::title2obj($orgTitleSubmittedByUser);
		if (is_null($org)) {
			return true;
		}

		$orgToEmail = Organization2Email::model()->findByAttributes(
			array('organization_id' => $org->id, 'user_email' => $userEmail)
		);

		return !empty($orgToEmail);
	}

	public static function syncSubmissions2Event($form)
	{
		$status = 'fail';
		$msg = '';

		$instructionValidated = false;
		$totalOrganizationSuccess = 0;
		$totalOrganizationFail = 0;
		$totalRegistrationSuccess = 0;
		$totalRegistrationFail = 0;

		// validate mapping instruction
		$instructionValidated = self::validateEventMappingInstruction($form->json_event_mapping);

		if ($instructionValidated) {
			// get mapping instuction
			$instruction = json_decode($form->json_event_mapping);

			// get event
			$event = Event::model()->findByPk($instruction->event_id);

			if ($instruction->is_sync_draft) {
				// get all submissionions including draft
				$submissions = FormSubmission::model()->findAll(
					array(
						'condition' => 'form_code=:form_code',
						'params' => array(':form_code' => $form->code),
					)
				);
			} else {
				// get all submissionions exluding draft
				$submissions = FormSubmission::model()->findAll(
					array(
						'condition' => 'form_code=:form_code AND status=:status',
						'params' => array(':form_code' => $form->code, ':status' => 'submit'),
					)
				);
			}

			//
			// sync eventOrganization
			if ($instruction->is_sync_organization) {
				// clear all existing eventOrganizations
				Yii::app()->db->createCommand()->delete('event_organization', 'event_code=:event_code AND event_vendor_code=:event_vendor_code', array(':event_code' => $event->code, ':event_vendor_code' => 'f7'));

				// loop thru all submissions
				foreach ($submissions as $submission) {
					//echo '<pre>';print_r($submission->jsonArray_data->startup_id);exit;

					// loop thru all fields associated with organization in instructions
					foreach ($instruction->organizations as $iOrganization) {
						$varStartupId = sprintf('%s_id', $iOrganization->map2modal);
						$varStartupName = $iOrganization->map2modal;
						$varAsRoleCode = $submission->stage;

						$eo = new EventOrganization();
						$eo->event_id = $event->id;
						$eo->event_code = $event->code;
						$eo->event_vendor_code = 'f7';
						$eo->date_action = $submission->date_submitted;
						$eo->organization_id = $submission->jsonArray_data->$varStartupId;
						$eo->organization_name = $submission->jsonArray_data->$varStartupName;
						$eo->as_role_code = $iOrganization->workflows->$varAsRoleCode;

						if ($eo->validate()) {
							if ($eo->save(false)) {
								$totalOrganizationSuccess++;
							} else {
								$totalOrganizationFail++;
							}
						} else {
							$totalOrganizationFail++;
						}
					}
				}

				$status = 'success';
			}

			// sync eventRegistration
			if ($instruction->is_sync_event_registration) {
				// clear all existing eventRegistrations
				Yii::app()->db->createCommand()->delete('event_registration', 'event_code=:event_code AND event_vendor_code=:event_vendor_code', array(':event_code' => $event->code, ':event_vendor_code' => 'f7'));

				// loop thru all submissions
				foreach ($submissions as $submission) {
					// loop thru all fields associated with organization in instructions
					foreach ($instruction->event_registrations as $iRegistration) {
						$er = new EventRegistration();
						$er->event_id = $event->id;
						$er->event_code = $event->code;
						$er->event_vendor_code = 'f7';

						// submitted application
						if (isset($submission->date_submitted)) {
							$er->date_registered = $submission->date_submitted;
						}
						// draft application
						else {
							$er->date_registered = $submission->date_modified;
						}

						$er->jsonArray_original = $submission->jsonArray_data;
						if (!empty($iRegistration->attendance_map_workflow) && in_array($submission->stage, $iRegistration->attendance_map_workflow)) {
							$er->is_attended = 1;
						} else {
							$er->is_attended = 0;
						}

						$fields = ['email', 'full_name', 'first_name', 'last_name', 'phone', 'gender', 'nationality', 'organization', 'age_group', 'where_found', 'persona'];

						foreach ($fields as $field) {
							$var = null;
							if ($iRegistration->mappings->$field) {
								if (strstr($iRegistration->mappings->$field, 'f7.')) {
									$var = str_replace('f7.', '', $iRegistration->mappings->$field);
									if (isset($submission->jsonArray_data->$var)) {
										$er->$field = $submission->jsonArray_data->$var;
									}
								}
								// direct value set
								else {
									$er->$field = $iRegistration->mappings->$field;
								}
							}
						}

						if (!empty($er->full_name) || !empty($er->email)) {
							if ($er->validate()) {
								if ($er->save()) {
									$totalRegistrationSuccess++;
								} else {
									$totalRegistrationFail++;
								}
							} else {
								$totalRegistrationFail++;
							}
						}
					}
				}
				$status = 'success';
			}

			$result = array('status' => $status, 'msg' => $msg, 'data' => array(
				'totalRegistrationSuccess' => $totalRegistrationSuccess, 'totalRegistrationFail' => $totalRegistrationFail, 'totalOrganizationSuccess' => $totalOrganizationSuccess, 'totalOrganizationFail' => $totalOrganizationFail, 'event' => $event,
			));

			// echo '<pre>';print_r($result);exit;

			return $result;
		}
	}

	public static function validateEventMappingInstruction($json)
	{
		return true;
	}

	public static function getOpeningForms($dateStart, $dateEnd, $page = 1)
	{
		$limit = 30;
		$status = 'fail';
		$msg = 'Unknown error';

		$timestampStart = strtotime($dateStart);
		$timestampEnd = strtotime($dateEnd) + (24 * 60 * 60);

		// date range can not be more than 60 days
		if (floor(($timestampEnd - $timestampStart) / (60 * 60 * 24)) > 60) {
			$msg = 'Max date range cannot more than 60 days';
		} else {
			$data = null;
			$forms = Form::model()->findAll(array(
				'condition' => 'is_active=1 AND (
					(:timestampStart >= date_open AND :timestampEnd <= date_close) 
					OR 
					(:timestampStart <= date_open AND :timestampEnd >= date_open) 
					OR 
					(:timestampStart <= date_close AND :timestampEnd >= date_close) 
					OR 
					(:timestampStart <= date_open AND :timestampEnd >= date_close) 
					)',
				'params' => array(':timestampStart' => $timestampStart, ':timestampEnd' => $timestampEnd),
				'offset' => ($page - 1) * $limit,
				'limit' => $limit,
				'order' => 'date_open DESC'
			));

			foreach ($forms as $form) {
				$data[] = $form->toApi(array('-jsonStructure'));
			}

			$status = 'success';
			$msg = '';
		}

		return array('status' => $status, 'msg' => $msg, 'data' => $data);
	}

	public static function formatCss($cssClass, $isEnabled)
	{
		// readonly
		if (!$isEnabled) {
			return str_replace('hidden', '', $cssClass);
		}

		return $cssClass;
	}

	// UPDATE: f7_multilingual
	public static function switchLanguage($value)
	{
		$language = Yii::app()->language;

		if (isset($language)) {
			// Check if empty before assignning. If empty, fallback to default values
			if (isset($value['value'])) {
				$value['value'] = isset($value['value-' . $language]) ? $value['value-' . $language] : $value['value'];
			}
			if (isset($value['text'])) {
				$value['text'] = isset($value['text-' . $language]) ? $value['text-' . $language] : $value['text'];
			}
			if (isset($value['hint'])) {
				$value['hint'] = isset($value['hint-' . $language]) ? $value['hint-' . $language] : $value['hint'];
			}
			if (isset($value['error'])) {
				$value['error'] = isset($value['error-' . $language]) ? $value['error-' . $language] : $value['error'];
			}
		}

		return $value;
	}
}
