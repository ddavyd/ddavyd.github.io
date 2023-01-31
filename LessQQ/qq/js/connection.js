const { Sequelize } = require('sequelize');

const sequelize = new Sequelize('guild', 'root', 'blabla132***', {
  host: 'localhost',
  dialect: 'mqsql'
});

try {
  await sequelize.authenticate();
  console.log('Connection has been established successfully.');
} catch (error) {
  console.error('Unable to connect to the database:', error);
}