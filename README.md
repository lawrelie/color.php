# color.php

```php
include 'path/to/color.php';
$color = new Lawrelie\Color\Color('#000');
echo $color->hexTripet;// '#000000'
echo $color->hsl;// [0, 0, 0]
echo $color->hslString;// 'hsl(0, 0%, 0%)'
echo $color->hsv;// [0, 0, 0]
echo $color->hsvString;// 'hsv(0, 0%, 0%)'
echo $color->rgb;// [0, 0, 0]
echo $color->rgbString;// 'rgb(0, 0, 0)'
echo $color->isDark;// true
echo $color->isLight;// false
echo $color->textColor;// new Lawrelie\Color\Color('#fff');
```
