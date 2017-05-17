var mathjaxServer = require("mathjax-server");
var config = require('./extension.json');
mathjaxServer.start(config.config.MathJaxServerPort);