<?php
$fastjson = Yaf_Application::app()->getConfig()->api->model->android->fastjson->enable;
$jackson = Yaf_Application::app()->getConfig()->api->model->android->jackson->enable;
$parcelable = Yaf_Application::app()->getConfig()->api->model->android->parcelable->enable;
$is_field_public = Yaf_Application::app()->getConfig()->api->model->android->field->public;
 
?>package <?php echo Yaf_Application::app()->getConfig()->api->model->android->package?>;


import java.io.Serializable;
import java.util.*;
<?php if($parcelable):?>
import android.os.Parcel;
import android.os.Parcelable;
<?php endif;?>
<?php if($jackson):?>
//import org.codehaus.jackson.*;
import com.fasterxml.jackson.core.*;
<?php endif;?>
<?php if($fastjson):?>
import com.alibaba.fastjson.JSON;
import com.alibaba.fastjson.annotation.JSONField;
<?php endif;?>


public class <?php echo $class->name?> implements Serializable<?php if($parcelable) echo ', Parcelable'?> {
    private static final long serialVersionUID = 1L;
<?php foreach($class->propertiesHash as $name=>$property):
  // 生成注释
	echo "    /** \n";
	foreach(explode("\n", $property->comment) as $comment) echo "     * ", $comment, "\n";
	echo "     */\n";


	// 生成变量声明
	if($fastjson) echo "    @JSONField(name = \"{$property->jsonName}\")\n";
	if($is_field_public) echo '    public ', $property->getAndroidType();
	else echo '    private ', $property->getAndroidType();
	if(strpos($property->getAndroidType(), '<')){ // 是一个数组
    echo ' ', $property->name, " = new ", $property->getAndroidType(), "();\n";
  }else
  	echo ' ', $property->name, ";\n";
  endforeach;?>


<?php if(!$is_field_public) foreach($class->propertiesHash as $name=>$property):
	// getter and setter
  // 生成注释
	echo "    /** \n";
	foreach(explode("\n", $property->comment) as $comment) echo "     * ", $comment, "\n";
	echo "     */\n";
	echo "    public {$property->getAndroidType()} get", ucfirst($property->name), "(){\n";
	echo "        return this.{$property->name};\n";
	echo "    }\n";
	echo "    public void set", ucfirst($property->name), "({$property->getAndroidType()} $property->name){\n";
	echo "        this.{$property->name} = {$property->name};\n";
	echo "    }\n";
	echo "\n\n";
endforeach;?>
  
    public <?php echo $class->name?>(){}

<?php if($jackson): // model init for jackson framework ?>
    public <?php echo $class->name?>(JsonParser parser) {
        try{
            while (true) {
                JsonToken nextToken = parser.nextToken();
                if(nextToken==JsonToken.START_OBJECT) continue;
                if(nextToken==JsonToken.END_OBJECT) break;
                if(nextToken==JsonToken.NOT_AVAILABLE) break;
                String name = parser.getCurrentName();
                JsonToken nextValue = parser.nextToken();
                if(nextValue==null) break;
<?php $i=0;foreach($class->propertiesHash as $name=>$property):
	$android_type = $property->getAndroidType();
	if($i>0) echo "else ";
	else echo "                ";
	echo "if(\"{$property->jsonName}\".equals(name)){\n";
	if($android_type=='String'){
    echo "                    if(nextValue==JsonToken.VALUE_STRING) {$property->name} = parser.getText();\n";
	}else if($android_type=='boolean'){
    echo "                    if(nextValue==JsonToken.VALUE_STRING) {$property->name} = !(parser.getText().equals(\"0\")||parser.getText().equals(\"false\"));\n";
    echo "                    else if(nextValue==JsonToken.VALUE_TRUE) {$property->name} = true;\n";
    echo "                    else if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name} = parser.getValueAsInt()!=0;\n";
	}else if($android_type=='int'){
    echo "                    if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name} = parser.getValueAsInt();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_STRING) {$property->name} = parser.getValueAsInt();\n";
	}else if($android_type=='long'){
    echo "                    if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name} = parser.getValueAsLong();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_STRING) {$property->name} = parser.getValueAsLong();\n";
	}else if($android_type=='float'){
    echo "                    if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name} = (float)parser.getValueAsDouble();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_STRING) {$property->name} = (float)parser.getValueAsDouble();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_NUMBER_FLOAT) {$property->name} = (float)parser.getValueAsDouble();\n";
	}else if($android_type=='double'){
    echo "                    if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name} = parser.getValueAsDouble();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_STRING) {$property->name} = parser.getValueAsDouble();\n";
    echo "                    else if(nextValue==JsonToken.VALUE_NUMBER_FLOAT) {$property->name} = parser.getValueAsDouble();\n";
	}else if($property->type=='array'){
		echo "                    {$property->name} = new ArrayList<{$property->getAndroidArrayValueType()}>();\n";
    echo "                    if(nextValue==JsonToken.START_ARRAY){\n";
    echo "                        while((nextValue=parser.nextToken()) != JsonToken.END_ARRAY){\n";
    $array_value_type = $property->getAndroidArrayValueType();
    if($array_value_type=='Boolean'){
			echo "                            if(nextValue==JsonToken.VALUE_STRING) {$property->name}.add(!(parser.getText().equals(\"0\")||parser.getText().equals(\"false\")));\n";
			echo "                            else if(nextValue==JsonToken.VALUE_TRUE) {$property->name}.add(true);\n";
			echo "                            else if(nextValue==JsonToken.VALUE_NUMBER_INT) {$property->name}.add(parser.getValueAsInt()!=0);\n";
			echo "                            else {$property->name}.add(false);\n";
    }elseif($array_value_type=='Integer'){
			echo "                            if(nextValue==JsonToken.VALUE_NUMBER_INT || nextValue==JsonToken.VALUE_STRING || nextValue==JsonToken.VALUE_NUMBER_FLOAT)\n";
			echo "                                {$property->name}.add(parser.getValueAsInt());\n";
    }elseif($array_value_type=='Long'){
			echo "                            if(nextValue==JsonToken.VALUE_NUMBER_INT || nextValue==JsonToken.VALUE_STRING || nextValue==JsonToken.VALUE_NUMBER_FLOAT)\n";
			echo "                                {$property->name}.add(parser.getValueAsLong());\n";
    }elseif($array_value_type=='Float'){
			echo "                            if(nextValue==JsonToken.VALUE_NUMBER_INT || nextValue==JsonToken.VALUE_STRING || nextValue==JsonToken.VALUE_NUMBER_FLOAT)\n";
			echo "                                {$property->name}.add((float)parser.getValueAsDouble());\n";
    }elseif($array_value_type=='Double'){
			echo "                            if(nextValue==JsonToken.VALUE_NUMBER_INT || nextValue==JsonToken.VALUE_STRING || nextValue==JsonToken.VALUE_NUMBER_FLOAT)\n";
			echo "                                {$property->name}.add(parser.getValueAsDouble());\n";
    }elseif($array_value_type=='String'){
			echo "                            if(nextValue==JsonToken.VALUE_STRING)\n";
			echo "                                {$property->name}.add(parser.getText());\n";
    }else
    	echo "                            {$property->name}.add(new {$property->arrayValueClass}(parser));\n";
    echo "                        }\n";
    echo "                    }\n";
	}else{
    echo "                    {$property->name} = new {$android_type}(parser);\n";
  }
	echo "                }";
	$i++;
endforeach; echo "else{skipSubTree(parser);}\n";?>
            }
        }catch(JsonParseException e){}catch(java.io.IOException e){}
    }
    
    private static void skipSubTree(JsonParser parser) throws JsonParseException, java.io.IOException {
        JsonToken eventType = parser.getCurrentToken();
        if(eventType == JsonToken.START_OBJECT) {
            int level = 1;
            while (level > 0) {
                eventType = parser.nextToken();
                if(eventType == JsonToken.END_OBJECT) {
                    --level;
                }else if(eventType == JsonToken.START_OBJECT) {
                    ++level;
                }
            }
        }else if(eventType == JsonToken.START_ARRAY) {
            int level = 1;
            while (level > 0) {
                eventType = parser.nextToken();
                if(eventType == JsonToken.END_ARRAY) {
                    --level;
                }else if(eventType == JsonToken.START_ARRAY) {
                    ++level;
                }
            }
        }
    }
<?php endif;?>

<?php if($parcelable):?>
    public int describeContents() {
        return 0;
    }
    public void writeToParcel(Parcel dest, int flags) {
        dest.writeString(JSON.toJSONString(this));
    }
    public static final Parcelable.Creator<<?php echo $class->name?>> CREATOR = new Parcelable.Creator<<?php echo $class->name?>>() {
        public <?php echo $class->name?> createFromParcel(Parcel in) {
            return new <?php echo $class->name?>(in);
        }
        public <?php echo $class->name?>[] newArray(int size) {
            return new <?php echo $class->name?>[size];
        }
    };
    private <?php echo $class->name?>(Parcel in) {
        <?php echo $class->name?> tmp = JSON.parseObject(in.readString(), <?php echo $class->name?>.class);
<?php foreach($class->propertiesHash as $name=>$property):?>
        this.<?php echo $name?> = tmp.<?php echo $name?>;
<?php endforeach;?>
    }
<?php endif;?>
}

