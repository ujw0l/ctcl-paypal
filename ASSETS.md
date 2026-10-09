# WordPress.org assets

The committed `assets/` folder contains three PNG screenshots with corresponding `readme.txt` captions and plugin icons at exactly 128×128 and 256×256. Copy the folder to the top-level WordPress.org SVN `assets/` directory, alongside `trunk/` and `tags/`. A GitHub push does not publish the WordPress.org listing.

Screenshot 1 shows the PayPal settings and appearance preview; screenshot 2 shows desktop checkout; screenshot 3 shows mobile checkout. All screenshots come from the running WordPress test site.

## Icon generation

Mode: built-in image generation. Reference: the user's `pp.png`. The final generated source is supplied as `paypal-icon-source.png`; the two directory sizes are resampled from that source.

Prompt:

> Use case: logo-brand
> Asset type: square WordPress.org plugin icon for CT Commerce Lite PayPal.
> Input image: reference for the existing plugin identity; redesign this identity cleanly.
> Primary request: Create a polished premium app icon retaining the reference's storefront emblem, PayPal wordmark, and CT Commerce Lite name. Remove the pasted white rectangle behind Commerce, eliminate the bulky concentric rings, blur and bevels, and unify the typography.
> Design: very pale icy blue solid square background. At the top a crisp centered PayPal wordmark in navy and bright blue, exactly 'PayPal'. A large clean navy circular medallion in the center containing a bold simple white storefront with a blue-and-white awning, just one restrained cyan rim accent. Bottom text centered and balanced on two lines, exactly 'CT Commerce' and 'Lite' in navy, clean bold sans serif. Icon mark must dominate and be recognizable at 128 pixels. Use broad bold shapes, plenty of negative space, flat vector-like style, sharp smooth edges, all elements comfortably within the square. No gradients, no photographic effects, no tiny ornaments, no unrelated wording, no watermark. Deliver one icon only.
