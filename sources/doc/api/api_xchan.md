API xchan
=========

An xchan is a global location independent channel and is the primary record for a network 
identity. It may refer to channels on other websites, networks, or services. 

GET /api/z/1.0/xchan

Required: one of [ address, hash, guid ] as GET parameters

Returns a portable xchan structure

Example: https://xyz.macgirvin.com/api/z/1.0/xchan?f=&address=mike@macgirvin.com

Returns:

	{
        "hash": "jr54M_y2l5NgHX5wBvP0KqWcAHuW23p1ld-6Vn63_pGTZklrI36LF8vUHMSKJMD8xzzkz7s2xxCx4-BOLNPaVA",
        "guid": "sebQ-IC4rmFn9d9iu17m4BXO-kHuNutWo2ySjeV2SIW1LzksUkss12xVo3m3fykYxN5HMcc7gUZVYv26asx-Pg",
        "guid_sig": "Llenlbl4zHo6-g4sa63MlQmTP5dRCrsPmXHHFmoCHG63BLq5CUZJRLS1vRrrr_MNxr7zob_Ykt_m5xPKe5H0_i4pDj-UdP8dPZqH2fqhhx00kuYL4YUMJ8gRr5eO17vsZQ3XxTcyKewtgeW0j7ytwMp6-hFVUx_Cq08MrXas429ZrjzaEwgTfxGnbgeQYQ0R5EXpHpEmoERnZx77VaEahftmdjAUx9R4YKAp13pGYadJOX5xnLfqofHQD8DyRHWeMJ4G1OfWPSOlXfRayrV_jhnFlZjMU7vOdQwHoCMoR5TFsRsHuzd-qepbvo3pzvQZRWnTNu6oPucgbf94p13QbalYRpBXKOxdTXJrGdESNhGvhtaZnpT9c1QVqC46jdfP0LOX2xrVdbvvG2JMWFv7XJUVjLSk_yjzY6or2VD4V6ztYcjpCi9d_WoNHruoxro_br1YO3KatySxJs-LQ7SOkQI60FpysfbphNyvYMkotwUFI59G08IGKTMu3-GPnV1wp7NOQD1yzJbGGEGSEEysmEP0SO9vnN45kp3MiqbffBGc1r4_YM4e7DPmqOGM94qksOcLOJk1HNESw2dQYWxWQTBXPfOJT6jW9_crGLMEOsZ3Jcss0XS9KzBUA2p_9osvvhUKuKXbNztqH0oZIWlg37FEVsDs_hUwUJpv2Ar09k4",
        "pubkey": "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA7QCwvuEIwCHjhjbpz3Oc\ntyei/Pz9nDksNbsc44Cm8jxYGMXsTPFXDZYCcCB5rcAhPPdZSlzaPkv4vPVcMIrw\n5cdX0tvbwa3rNTng6uFE7qkt15D3YCTkwF0Y9FVZiZ2Ko+G23QeBt9wqb9dlDN1d\nuPmu9BLYXIT/JXoBwf0vjIPFM9WBi5W/EHGaiuqw7lt0qI7zDGw77yO5yehKE4cu\n7dt3SakrXphL70LGiZh2XGoLg9Gmpz98t+gvPAUEotAJxIUqnoiTA8jlxoiQjeRK\nHlJkwMOGmRNPS33awPos0kcSxAywuBbh2X3aSqUMjcbE4cGJ++/13zoa6RUZRObC\nZnaLYJxqYBh13/N8SfH7d005hecDxWnoYXeYuuMeT3a2hV0J84ztkJX5OoxIwk7S\nWmvBq4+m66usn6LNL+p5IAcs93KbvOxxrjtQrzohBXc6+elfLVSQ1Rr9g5xbgpub\npSc+hvzbB6p0tleDRzwAy9X16NI4DYiTj4nkmVjigNo9v2VPnAle5zSam86eiYLO\nt2u9YRqysMLPKevNdj3CIvst+BaGGQONlQalRdIcq8Lin+BhuX+1TBgqyav4XD9K\nd+JHMb1aBk/rFLI9/f2S3BJ1XqpbjXz7AbYlaCwKiJ836+HS8PmLKxwVOnpLMbfH\nPYM8k83Lip4bEKIyAuf02qkCAwEAAQ==\n-----END PUBLIC KEY-----\n",
        "photo_mimetype": "image/jpeg",
        "photo_l": "https://xyz.macgirvin.com/photo/350b74555c04429148f2e12775f6c403-4",
        "photo_m": "https://xyz.macgirvin.com/photo/350b74555c04429148f2e12775f6c403-5",
        "photo_s": "https://xyz.macgirvin.com/photo/350b74555c04429148f2e12775f6c403-6",
        "address": "mike@macgirvin.com",
        "url": "https://macgirvin.com/channel/mike",
        "connurl": "https://macgirvin.com/poco/mike",
        "follow": "https://macgirvin.com/follow?f=&url=%s",
        "connpage": "https://macgirvin.com/connect/mike",
        "name": "Mike Macgirvin",
        "network": "zot",
        "instance_url": "",
        "flags": "0",
        "photo_date": "2012-12-06 05:06:11",
        "name_date": "2012-12-06 04:59:13",
        "hidden": "1",
        "orphan": "0",
        "censored": "0",
        "selfcensored": "0",
        "system": "0",
        "pubforum": "0",
        "deleted": "0"
	}