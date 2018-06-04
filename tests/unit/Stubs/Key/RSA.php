<?php

namespace Acme2\Tests\Unit\Stubs\Key;



class RSA extends \Karl\Acme2\Key\RSA
{
    public function __construct($pem = null, $bits = 2048)
    {
        $pem = <<<EOT
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCw1z26fp4VeqXz
Nar5XshdGpc4mRvhvTWLCLJjax6Z24N7vJf5cjJWtaprVAyZ8M7S/NtatkkodHXX
/iffTMFw71fsCHM2360hWjLU4FQQLTei4BT2t6FrU9kCiJv+tU9xm5KNnmAPGk8S
0yucoEjJLsCvfC/Lu8ZCPkXzVbCGkfU+HfEgImRfFefpKAuA21y4wJmDWDzneVCE
e5BbviBqW7bHg/02T8IA3/2VoEiN8ZXF7fpNoVbwmQLjRB/UvueG3vl8Lfk1XO1y
ORkMfC8weTdYvSGBx4yk78kJOPnfo5mCjM1kFBKMRzU44inL/P089Rtt59CaG8EO
4y25/w3RAgMBAAECggEAOMfz3xTWx4jJDi2WR1nCtfpaweaPiE0Liyfwt3FmsvpD
3pAwr/yV4zeTj8C+BxPGJQLhn9/V37J9QCwwO0fW+N8w+O3BqMXrPkFK/wVEmKkc
KyUONiXCI8cZb/HTPNaUuqK8TNKkf1TwPNgnMbRZipeDcRVL93vEbfK23SoPczWT
VJOzz3G/bIBEFlZp+tyACkzNXvFvo0mLuGLM2z2RVM3RMGfsSELAK0BVvaayvJrd
mUM7GY7Gzd56p0jASrBZ1p4482LyoJ3Mq8PhxsjooEm8VU+B/8y6ynuN0JcQQ/li
CDgQMc1JzA16/D5/Y3DrmADHtDPXPghVhEeQrmDd3QKBgQDm23pTHrrMD9sIZAun
y+b8CHxB1mmJ+nfqi2oy4Qc8mYPmYBMe3ps8xnwW9HfTw9OA0QjRysB4loyp4C9a
yu9b1Pg573WeiyN7v1KykooPToEb52JmhbyOlveujPavnQoCgb9nneNR+gEzoVHK
hFHsE1CgnTjB33XYkTzHwrR0CwKBgQDEGbsBwudDExM0yxGn1Kb4KhhIZg7k5KU+
e/zA9OmQj/M5InwvsQY7Nnd/7ZCKQhAKUoX+qg2SlsNcMKw5VfWX2US1SWWqJdub
jb1Y2t9ordXXZnS8kxHPYoceRGJMqAQyVoHxElERoFuZgb7GsCFT/TJ3tpHkKc9c
morOqdzzEwKBgEyfXCJqeKVyPcizLAstaiUMy/EuMSlSsKpwS3dHqRc7/MNh7/a2
+99YIaeczIjE3lZLCxpWqTtc/KMFfbIs2PUp4pmYGPneRJC2F8SdTqV18PRKACb3
DHZnNR4CO35eKQxb9CN4DBMRX4S7bmJBOM+aOZCVnlj6yipvSjUjMrGxAoGAY8Tv
hvrhbBfFWsPCLNAdTv8jsZnzE/NcOwkb1BQGzNzgEv5/jkgTcobIj5aPRjhMLuRD
dgiQtTbtF/dPeDBKrkIFGfLIvaNbMq4PWqiop3ph5KAk3lg45HktY3HJTVTiJbYr
kDoQctZSJCyFolKz8iZMyeliGmJHNMcPvgtf9W8CgYEAxNtcntMHdVKZfX5I6tSq
SEKFZNe2wbmKpl2OnQe093l2esRhYLQkVeXsw9A9v+Tn7yjGRkpkJ/NSutxh3H9o
azwib1pbOfPM9sHosGXCLY/eZCb/zH4scjgwm0phxA3XrowbY7s6HvvvLbOf8bEX
SAtxBUFFzHUF3bo6rDQWwi8=
-----END PRIVATE KEY-----
EOT;

        parent::__construct($pem, $bits);
    }
}

