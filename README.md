Magic Loader
============

A CodeIgniter Spark for auto-magically taking care of loading modules.

Magic_load is a CodeIgniter Sparks library that attempts to remove all the tediousness out of loading libraries manually.
All you need to do is include Magic_load as the only item of your autoload config file:

	$autoload['sparks'] = array('magic_load/1.0.0');

... And from now on all models, libraries and helpers will be automatically loaded as needed.

So:

	$this->load->library('Fancy_library');
	$this->fancy_library->do_things('foo', 'bar');

	$this->load->model('User');
	$userid = $this->User->GetById(123);

Can be just:

	$this->fancy_library->do_things('foo', 'bar');
	$userid = $this->User->GetById(123);

... Magic loader will take care of figuring out what modules to load and attempt to bring them into the mix as needed.

Even complex relationships like Models calling other models, libraries or helpers will be dealt with. You can make as many interconnected relationships as you wish without loading them in your controller.


Technical info
==============

What Magic_load is
------------------

Magic_load is, frankly, a horrible kludge to work around one of the unfortunate side-effects of the otherwise excellent CodeIgniter framework. I have in the past created automagicly loading modules which worked fine prior to the CodeIgniter version 2.0.0 apocalypse.
Unfortunately I can't get any of the wonderful modules to work any more without actually reprogramming the CodeIgniter core - which wouldn't be very forward compatible as the framework continues to grow.

Magic_load works by (brace yourself) scanning your source code for mentions of external modules. Specifically it looks for anything attached to the '$this' object. So for example:

	$this->User->GetById(123);

... which is obviously a call to the User model. Magic_load recognises this and tries to load the User model for you whenever you use a controller that has something like the above code.


Naturally this is exceptionally slow in server terms since the source code needs to be scanned each time the page is loaded. To work around this issue, Magic_load uses a caching system where it examines the last time a source code file was altered and re-generates its module list based on that. This means that Magic_load can change its behaviour for systems that are under active development but still stay relatively speedy on production systems.

This leads us naturally to...


What Magic_load isn't
---------------------

... A permanent solution to the problem. Please don't bitch at me about this not being workable in the long run. I _have_ solutions which allows CodeIgniter to automatically load modules on the fly, unfortunately all of them involve changing the original CodeIgniter code library source code.

The intention of Magic_load is to provide a reasonably easy way of loading modules on the fly without breaking future upgrades of the CodeIgniter library.

Hopefully the CodeIgniter guys (who are incredibly handsome by the way) will one day get around to including autoloading - and if they have any issues please email me since this is my #1 inclusion wish.


Known issues
============
* Recursion outside the original controller layer is unsupported, meaning:
	- Models are currently unsupported
	- Libraries are currently unsupported
	- Helpers are currently unsupported
* Caching does not yet work
* Anything other than simple $this->[module] will not be detected as a module - frankly parsing the entire language is a pain


About the author
================

I do solemnly swear that the above is a kludge and by _far_ not the optimal solution to the problem of (true) autoloading for CodeIgniter.

If anyone has any suggestions on a less revolting method, I am all ears.

- Matt Carter (MC)
