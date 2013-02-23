## Usage

```php
  $get = Mingle::get('users')
          ->where('user_id', '=', $user_id)
          ->or_where('name', '=', 'Melvin')
          ->order_by('time', 'DESC')
          ->and_is_not_null('fb_access_token')
          ->execute();

  if ($get->not_empty_success)
  {
    $user = $get->item(); // first item as Mingle_Item object
    $users = $get->items; // flat array
    $users = $get->items(); // list of Mingle_Item objects
    
    foreach ($users as $user)
    {
      $user->age += 1;
      $user->save();
    }
  }

```
    
