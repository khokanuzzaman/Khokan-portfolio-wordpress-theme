(function (wp, $) {
  if (!wp || !wp.customize) {
    return;
  }

  function parseSeat(value) {
    var num = parseInt(value, 10);
    if (isNaN(num)) {
      return 0;
    }
    return Math.max(0, num);
  }

  function isPlaceholder(value) {
    if (!value) {
      return true;
    }
    return /^\s*(\{\{[^}]+\}\}|\[[^\]]+\]|\{[^}]+\})\s*$/.test(value);
  }

  function computeRemaining() {
    var seatSetting = wp.customize("jrc_options[hero_card_seat_limit]");
    var batchSetting = wp.customize("jrc_options[details_batch]");
    var bookedSetting = wp.customize("jrc_options[booked_seats]");
    var seatLimit = seatSetting ? seatSetting.get() : "";

    if (isPlaceholder(seatLimit)) {
      seatLimit = batchSetting ? batchSetting.get() : "";
    }

    var total = parseSeat(seatLimit);
    var booked = bookedSetting ? parseSeat(bookedSetting.get()) : 0;
    var remaining = Math.max(0, total - booked);

    var control = wp.customize.control("jrc_options[remaining_seats]");
    if (control) {
      control.container.find("input").val(String(remaining));
    }
  }

  function bindSetting(id) {
    var setting = wp.customize(id);
    if (!setting) {
      return;
    }
    setting.bind(computeRemaining);
  }

  wp.customize.bind("ready", function () {
    bindSetting("jrc_options[hero_card_seat_limit]");
    bindSetting("jrc_options[details_batch]");
    bindSetting("jrc_options[booked_seats]");
    computeRemaining();
  });
})(window.wp, jQuery);
