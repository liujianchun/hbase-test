root /home/webuser/htdocs/hbase_test;
index index.php index.html index.htm;

proxy_set_header X-Real-IP $remote_addr;
proxy_set_header Host $host;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
set_real_ip_from 192.168.0.0/16;
set_real_ip_from 127.0.0.0/16;

gzip on;
gzip_types text/xml text/plain application/x-javascript application/javascript text/css application/xml application/json;

location / {
  try_files $uri $uri/ /index.php?$args;
}

location ~ .*\.(gif|jpg|png|htm|html|css|js|ico|swf)$ {
  if (-f $request_filename) {
    expires 90d;
    break;
  }
}

location /conf/ {
  deny all;
  log_not_found off;
}

location /vendor/ {
  deny all;
  log_not_found off;
}

location /.git {
  deny all;
  log_not_found off;
}

location /.svn {
  deny all;
  log_not_found off;
}

location ~ \.php$ {
  fastcgi_pass   127.0.0.1:9001;
  fastcgi_index  index.php;
  include        fastcgi_params;
  fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
}

location /application/ {
  deny all;
  log_not_found off;
}
