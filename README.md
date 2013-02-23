Usage

```php
  $get = Mingle::get('users')
          ->where('user_id', '=', $user_id)
          ->or_where('name', '=', 'Melvin')
          ->order_by('time', 'DESC')
          ->and_is_not_null('fb_access_token')
          ->execute();

  if ($get->not_empty_success)
  {
    $item = $get->item(); // first item as Mingle_Item object
    $items = $get->items; // flat array
    $items = $get->items(); // list of Mingle_Item objects
    
    foreach ($items as $item)
    {
      $item->age += 1;
      $item->save();
    }
  }

```
    
