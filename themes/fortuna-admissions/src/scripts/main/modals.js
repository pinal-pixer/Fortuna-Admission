import MicroModal from "micromodal";

const Modals = {
  init() {
    const modalConfig = {
      openClass: "is-open",
      disableScroll: true,
      disableFocus: true,
      awaitOpenAnimation: true,
      awaitCloseAnimation: true,
    };
    
    MicroModal.init(modalConfig);
  }
};

export default Modals;