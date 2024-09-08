(($) => {
    'use strict';

    // Add a status flag
    let isProcessing = false;

        $(document).on('click','.clip-copy', function(e) {
            // Block if still processing
        if (isProcessing) {
            return;
        }
        // Set flag to true
        isProcessing = true;

        copy(e.target);

        // Reset flag when operation done
        setTimeout(() => {
            isProcessing = false;
        }, 1000);
    });

    const copy = (el) => {
        let txt = $(el).prev();

        // Check if txt actually exists
        if (txt.length === 0 || !txt.val()) {
            // Handle error here
            console.warn('No text found');
            return;
        }

        navigator.clipboard
          .writeText(txt.val())
          .then(() => {
              $(el).addClass('success');
              setTimeout(() => {
                  $(el).removeClass('success');
              }, 1000);

          })
          .catch((err) => {
              $(el).addClass('error');
              setTimeout(() => {
                  $(el).removeClass('error');
              }, 1000);

              console.warn(err);
          });
    };
})(jQuery);