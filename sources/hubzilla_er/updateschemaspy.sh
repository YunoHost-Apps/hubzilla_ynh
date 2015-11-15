#!/bin/bash
# Use schemaSpy to generate HTML-reports about tables in Hubzilla running on OpenShift.
# You will need to port-forward your app on OpenShift, like this
# rhc port-forward zot  
java -jar /home/haakon/Nedlastinger/schemaSpy_5.0.0.jar -t mysql -host 127.0.0.1:3306 -db zot -u adminkwvcHXy -p g66nhPmZ9b52 -dp /home/haakon/Nedlastinger/mysql-connector-java-5.1.17.jar -o .
