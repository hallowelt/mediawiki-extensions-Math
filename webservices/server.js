var mathjaxServer = require("../node_modules/mathjax-server");
var config = require('../extension.json');
mathjaxServer.start(config.config.MathJaxServerPort);