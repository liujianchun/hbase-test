<?php

$ios_system_types_hash = array(
		'NSMutableArray' => true,
		'NSMutableDictionary' => true,
		'NSMutableArray' => true,
		'NSInteger' => true,
		'NSString' => true,
    'NSDate' => true,
		'CGFloat' => true,
		'bool' => true,
		'long long' => true,
		'double' => true,
);
$ios_assign_types_hash = array(
		'NSInteger' => true,
		'CGFloat' => true,
		'bool' => true,
		'long long' => true,
		'double' => true,
);


$import_classes = array(); // 要import进来的类
foreach($class->propertiesHash as $name=>$property){
	$ios_type = $property->getIosType();
	if(!$ios_type) continue;
	if($ios_assign_types_hash[$ios_type]) continue;
	if(!$ios_system_types_hash[$ios_type]){
		$import_classes[$ios_type] = true;
	}
	if($property->getIosArrayValueClass() && !$ios_system_types_hash[$property->getIosArrayValueClass()]){
		$import_classes[$property->getIosArrayValueClass()] = true;
	}
	if($property->dictValueClass && !$ios_system_types_hash[$property->dictValueClass]){
		$import_classes[$property->dictValueClass] = true;
	}
}
?>


<?php foreach($import_classes as $c=>$temp):?>
#import "<?php echo $c?>.h"
<?php endforeach?>

@interface <?php echo $class->getIosName()?> : NSObject

<?php foreach($class->propertiesHash as $name=>$property):?>
<?php foreach(explode("\n", $property->comment) as $comment) echo '/// ', $comment, "\n";?>
@property(nonatomic, <?php echo ($ios_assign_types_hash[$property->getIosType()]) 
? "assign){$property->getIosType()} " 
: "retain){$property->getIosType()} *", $property->name?>;

<?php endforeach;?>

- (id)initWithJsonObject:(NSDictionary *)jsonObject;

@end

