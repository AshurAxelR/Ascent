# Ascent

_Chores and activity tracking web app._

**Important!** The app is designed to be used locally (on the `localhost`) by a single user. It has no built-in authentication or privacy protection. It is imperative to guard it off from the outside access. For example, on the Apache server, you can add the following `.htaccess` file:

```
order deny,allow
deny from all
allow from 127.0.0.1
allow from ::1
allow from localhost
```

### Screenshots

![Asc Screenshot](screenshots/asc.png?raw=true)

![Asc:Total Screenshot](screenshots/total.png?raw=true)

![Asc:Chores Screenshot](screenshots/chores.png?raw=true)

