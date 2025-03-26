const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"debugbar.openhandler":{"uri":"_debugbar\/open","methods":["GET","HEAD"]},"debugbar.clockwork":{"uri":"_debugbar\/clockwork\/{id}","methods":["GET","HEAD"],"parameters":["id"]},"debugbar.assets.css":{"uri":"_debugbar\/assets\/stylesheets","methods":["GET","HEAD"]},"debugbar.assets.js":{"uri":"_debugbar\/assets\/javascript","methods":["GET","HEAD"]},"debugbar.cache.delete":{"uri":"_debugbar\/cache\/{key}\/{tags?}","methods":["DELETE"],"parameters":["key","tags"]},"debugbar.queries.explain":{"uri":"_debugbar\/queries\/explain","methods":["POST"]},"login":{"uri":"login","methods":["GET","HEAD"]},"login.store":{"uri":"login","methods":["POST"]},"logout":{"uri":"logout","methods":["POST"]},"password.request":{"uri":"forgot-password","methods":["GET","HEAD"]},"password.reset":{"uri":"reset-password\/{token}","methods":["GET","HEAD"],"parameters":["token"]},"password.email":{"uri":"forgot-password","methods":["POST"]},"password.update":{"uri":"reset-password","methods":["POST"]},"register":{"uri":"register","methods":["GET","HEAD"]},"register.store":{"uri":"register","methods":["POST"]},"user-profile-information.update":{"uri":"user\/profile-information","methods":["PUT"]},"user-password.update":{"uri":"user\/password","methods":["PUT"]},"password.confirm":{"uri":"user\/confirm-password","methods":["GET","HEAD"]},"password.confirmation":{"uri":"user\/confirmed-password-status","methods":["GET","HEAD"]},"password.confirm.store":{"uri":"user\/confirm-password","methods":["POST"]},"two-factor.login":{"uri":"two-factor-challenge","methods":["GET","HEAD"]},"two-factor.login.store":{"uri":"two-factor-challenge","methods":["POST"]},"two-factor.enable":{"uri":"user\/two-factor-authentication","methods":["POST"]},"two-factor.confirm":{"uri":"user\/confirmed-two-factor-authentication","methods":["POST"]},"two-factor.disable":{"uri":"user\/two-factor-authentication","methods":["DELETE"]},"two-factor.qr-code":{"uri":"user\/two-factor-qr-code","methods":["GET","HEAD"]},"two-factor.secret-key":{"uri":"user\/two-factor-secret-key","methods":["GET","HEAD"]},"two-factor.recovery-codes":{"uri":"user\/two-factor-recovery-codes","methods":["GET","HEAD"]},"profile.show":{"uri":"user\/profile","methods":["GET","HEAD"]},"other-browser-sessions.destroy":{"uri":"user\/other-browser-sessions","methods":["DELETE"]},"current-user-photo.destroy":{"uri":"user\/profile-photo","methods":["DELETE"]},"current-user.destroy":{"uri":"user","methods":["DELETE"]},"sanctum.csrf-cookie":{"uri":"sanctum\/csrf-cookie","methods":["GET","HEAD"]},"overview":{"uri":"overview","methods":["GET","HEAD"]},"buildings":{"uri":"buildings","methods":["GET","HEAD"]},"buildings.update":{"uri":"admin\/buildings\/{id}","methods":["PUT"],"parameters":["id"]},"shipyard":{"uri":"shipyard","methods":["GET","HEAD"]},"shipyard.update":{"uri":"shipyard\/{spacecraft}\/update","methods":["POST"],"parameters":["spacecraft"],"bindings":{"spacecraft":"id"}},"shipyard.unlock":{"uri":"shipyard\/{spacecraft}\/unlock","methods":["POST"],"parameters":["spacecraft"],"bindings":{"spacecraft":"id"}},"market":{"uri":"market","methods":["GET","HEAD"]},"market.buy":{"uri":"market\/buy","methods":["POST"]},"market.sell":{"uri":"market\/sell","methods":["POST"]},"logbook":{"uri":"logbook","methods":["GET","HEAD"]},"research":{"uri":"research","methods":["GET","HEAD"]},"index":{"uri":"asteroidMap","methods":["GET","HEAD"]},"update":{"uri":"asteroidMap\/update","methods":["POST"]},"combat":{"uri":"asteroidMap\/combat","methods":["POST"]},"search":{"uri":"asteroidMap\/search","methods":["GET","HEAD"]},"asteroid":{"uri":"asteroidMap\/asteroid\/{asteroid}","methods":["GET","HEAD"],"parameters":["asteroid"],"bindings":{"asteroid":"id"}},"simulator":{"uri":"simulator","methods":["GET","HEAD"]},"simulator.simulate":{"uri":"simulator","methods":["POST"]},"resources.add":{"uri":"resources\/add","methods":["POST"]},"dashboard":{"uri":"admin\/dashboard","methods":["GET","HEAD"]},"user.show":{"uri":"admin\/user\/{id}","methods":["GET","HEAD"],"parameters":["id"]},"stations.update":{"uri":"admin\/stations\/{id}","methods":["PUT"],"parameters":["id"]},"resources.update":{"uri":"admin\/resources\/{id}","methods":["PUT"],"parameters":["id"]},"spacecrafts.update":{"uri":"admin\/spacecrafts\/{id}","methods":["PUT"],"parameters":["id"]},"spacecrafts.unlock":{"uri":"admin\/spacecrafts\/unlock","methods":["POST"]},"market.update":{"uri":"admin\/market\/{id}","methods":["PUT"],"parameters":["id"]},"queue.finish":{"uri":"admin\/queue\/finish\/{userId}","methods":["POST"],"parameters":["userId"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
