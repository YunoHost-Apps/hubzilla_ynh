This directory contains *browser valid* certs for use with hubzilla when using curl (server to server communication). The cacert.pem file in this directory is downloaded (when necessary) from the curl repository and tracks the Mozilla built-in certs. Additionally we've discovered we occasionally require intermediate certs from some cert providers which Mozilla and other browsers obtain automatically but curl does not. You may add these here if required. All these files are concatenated to create the library/cacert.pem file which we will use.  

Obtain the converted mozilla certs here:
http://curl.haxx.se/docs/caextract.html

Store as cacert.pem in this directory and then

cat *.pem > ../cacert.pem

to generate the master file in /library/cacert.pem


