Configure a custom page to be used for a logged out user when viewing the home page.

util/config system custom_home landingpage

landingpage is a relative link.

EG, util/config system custom_home channel/me will send logged out users to example.com/channel/me

To set a random channel (replacing random_channel_home) use:

util/config system custom_home random

