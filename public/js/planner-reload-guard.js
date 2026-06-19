/**
 * Evita que reloads concorrentes (polling + exclusão manual) sobrescrevam a UI com dados antigos.
 */
(function () {
  let generation = 0;

  window.plannerBeginReload = function () {
    return ++generation;
  };

  window.plannerIsReloadCurrent = function (gen) {
    return gen === generation;
  };

  window.plannerInvalidateReloads = function () {
    generation++;
  };
})();
