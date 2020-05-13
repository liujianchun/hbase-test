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


$property_sets = array();
foreach($class->propertiesHash as $name=>$property){
	$ios_type = $property->getIosType();
  $item  = "if([key isEqualToString:@\"{$property->jsonName}\"]){\n";
  switch($ios_type){
    case 'NSInteger':
      $item .= "        if([value respondsToSelector:@selector(intValue)])\n";
      $item .= "          _{$name} = [value intValue];\n";
      break;
    case 'CGFloat':
      $item .= "        if([value respondsToSelector:@selector(floatValue)])\n";
      $item .= "          _{$name} = [value floatValue];\n";
      break;
    case 'double':
      $item .= "        if([value respondsToSelector:@selector(floatValue)])\n";
      $item .= "          _{$name} = [value doubleValue];\n";
      break;
    case 'long long':
      $item .= "        if([value respondsToSelector:@selector(longLongValue)])\n";
      $item .= "          _{$name} = [value longLongValue];\n";
      break;
    case 'bool':
      $item .= "        if([value respondsToSelector:@selector(intValue)])\n";
      $item .= "          _{$name} = ([value intValue]!=0);\n";
      break;
    case 'NSString':
      $item .= "        if([value isKindOfClass:NSString.class])\n";
      $item .= "#if __has_feature(objc_arc)\n";
      $item .= "          _{$name} = value;\n";
      $item .= "#else\n";
      $item .= "          _{$name} = [value retain];\n";
      $item .= "#endif\n";
      break;
    case 'NSMutableArray':
    	$arrayValueClass = $property->getIosArrayValueClass();
      $item .= "        if([value isKindOfClass:NSArray.class]){\n";
      $item .= "          _{$name} = [[NSMutableArray alloc] initWithCapacity:[value count]];\n";
      if($arrayValueClass=='NSString'){
      	$item .= "          for(id v in value)\n";
      	$item .= "            if([v isKindOfClass:{$arrayValueClass}.class])\n";
      	$item .= "              [_{$name} addObject:v];\n";
      }elseif($arrayValueClass=='NSInteger'){
      	$item .= "          for(id v in value)\n";
      	$item .= "            if([v respondsToSelector:@selector(intValue)])\n";
      	$item .= "              [_{$name} addObject:[NSNumber numberWithInt:[v intValue]]];\n";
      }elseif($arrayValueClass=='CGFloat'){
      	$item .= "          for(id v in value)\n";
      	$item .= "            if([v respondsToSelector:@selector(floatValue)])\n";
      	$item .= "              [_{$name} addObject:[NSNumber numberWithFloat:[v floatValue]]];\n";
      }elseif($arrayValueClass=='double'){
      	$item .= "          for(id v in value)\n";
      	$item .= "            if([v respondsToSelector:@selector(doubleValue)])\n";
      	$item .= "              [_{$name} addObject:[NSNumber numberWithDouble:[v doubleValue]]];\n";
      }else{
	      $item .= "          for(NSDictionary *d in value){\n";
	      $item .= "            if(![d isKindOfClass:NSDictionary.class]) continue;\n";
	      $item .= "#if __has_feature(objc_arc)\n";
	      $item .= "            [_{$name} addObject:[[{$arrayValueClass} alloc] initWithJsonObject:d]];\n";
	      $item .= "#else\n";
	      $item .= "            [_{$name} addObject:[[[{$arrayValueClass} alloc] initWithJsonObject:d] autorelease]];\n";
	      $item .= "#endif\n";
	      $item .= "          }\n";
      }
      $item .= "        }\n";
      break;
    case 'NSMutableDictionary':
    	$item .= "        _{$name} = [[NSMutableDictionary alloc] initWithCapacity:[value count]];\n";
    	$item .= "        if([value isKindOfClass:NSDictionary.class])\n";
    	$item .= "          for(NSString *k in value){\n";
    	$item .= "            id v = [value objectForKey:k];\n";
    	$item .= "#if __has_feature(objc_arc)\n";
    	$item .= "            [_{$name} setObject:[[{$property->dictValueClass} alloc] initWithJsonObject:v] forKey:k];\n";
    	$item .= "#else\n";
    	$item .= "            [_{$name} setObject:[[[{$property->dictValueClass} alloc] initWithJsonObject:v] autorelease] forKey:k];\n";
    	$item .= "#endif\n";
    	$item .= "          }\n";
    	break;
  }
  if(!$ios_system_types_hash[$ios_type]){
  	$item .= "        if([value isKindOfClass:NSDictionary.class])\n";
  	$item .= "          _{$name} = [[{$property->arrayValueClass} alloc] initWithJsonObject:value];\n";
  }
  $item .= "      }";
  $property_sets[] = $item;
}



?>

#import "<?php echo $class->getIosName()?>.h"

@implementation <?php echo $class->getIosName()?>


<?php foreach($class->propertiesHash as $name=>$property):?>
@synthesize <?php echo $name?>=_<?php echo $name?>;
<?php endforeach?>

- (id)initWithJsonObject:(NSDictionary *)dictionary {
  if(![dictionary isKindOfClass:[NSDictionary class]]) return nil;
  self = [super init];
  if (self){
    for(NSString *key in dictionary){
      id value = [dictionary objectForKey:key];
      <?php echo join('else ', $property_sets)?>
      
    }
  }
  return self;
}




#if !__has_feature(objc_arc)
- (void)dealloc {
  <?php foreach($class->propertiesHash as $name=>$property):
  if($ios_assign_types_hash[$property->getIosType()]) continue?>[_<?php echo $name?> release];
  <?php endforeach;?>[super dealloc];
}
#endif
@end

