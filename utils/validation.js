const validator = require("validator");

const MIN_PASSWORD_LENGTH = 8;
const MAX_PASSWORD_LENGTH = 72;

function isValidEmail(value) {
  return typeof value === "string" && validator.isEmail(value);
}

function isValidPassword(value) {
  return typeof value === "string"
    && validator.isLength(value, { min: MIN_PASSWORD_LENGTH, max: MAX_PASSWORD_LENGTH });
}

function isValidRequiredText(value) {
  return typeof value === "string" && value.trim().length > 0;
}

module.exports = {
  isValidEmail,
  isValidPassword,
  isValidRequiredText
};
