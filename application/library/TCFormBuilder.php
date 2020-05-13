<?php
/**
 * @name TCApiControllerBase
 * @author liujianchun
 */
class TCFormBuilder{
	
	public static function label($model, $attribute, $label, $options=array()){
		$class_name = get_class($model);
		$options['for'] = "{$class_name}_{$attribute}";
		return TCHtml::element("label", $label, $options);
	}
	
	public static function hiddenField($model, $attribute, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		$options['value'] = $model->$attribute;
		$options['name'] = "{$class_name}[{$attribute}]";
		$options['type'] = 'hidden';
		return TCHtml::element("input", "", $options);
	}
	
	public static function textField($model, $attribute, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		if(isset($model->$attribute))
			$options['value'] = $model->$attribute;
		$options['name'] = "{$class_name}[{$attribute}]";
		return TCHtml::element("input", "", $options);
	}
	
	public static function fileField($model, $attribute, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		$options['name'] = "{$class_name}[{$attribute}]";
		$options['type'] = "file";
		return TCHtml::element("input", "", $options);
	}
	
	public static function textArea($model, $attribute, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		$options['name'] = "{$class_name}[{$attribute}]";
		return TCHtml::element("textarea", $model->$attribute, $options);
	}

	public static function dateField($model, $attribute, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		$options['value'] = $model->$attribute;
		$options['name'] = "{$class_name}[{$attribute}]";
		$options['type'] = "date";
		return TCHtml::element("input", "", $options);
	}
	
	public static function selectField($model, $attribute, $data, $options=array()){
		$class_name = get_class($model);
		$options['id'] = "{$class_name}_{$attribute}";
		$options['name'] = "{$class_name}[{$attribute}]";
		$options_string = "";
		foreach($data as $k=>$v){
			if($k==$model->$attribute)
				$options_string .= "<option value=\"{$k}\" selected=\"selected\">{$v}</option>";
			else
				$options_string .= "<option value=\"{$k}\">{$v}</option>";
		}
		return TCHtml::element("select", $options_string, $options, false);
	}
	
	public static function checkboxList($model, $attribute, $data, $options=array()){
		$html = "";
		$class_name = get_class($model);
		foreach($data as $value=>$label){
			$input = "<input name=\"{$class_name}[{$attribute}][]\" value=\"{$value}\" type=\"checkbox\"";
			if(in_array($value, $model->$attribute)){
				$input .= ' checked="checked"';
			}
			$input .= " />";
			$html .= TCHtml::element("label", $input . htmlspecialchars($label), $options, false);
		}
		$html .= '<a href="javascript:void(0)" class="checkbox-list-check-all">全选</a>';
		return $html;
	}
}

