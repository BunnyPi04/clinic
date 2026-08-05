import { useEffect, useRef, useState } from "react";
import StatusBadge, { statusConfig } from "./StatusBadge";

export default function StatusFilterDropdown({
  value,
  onChange,
  includeAll = true,
}) {
  const [isOpen, setIsOpen] = useState(false);
  const wrapperRef = useRef(null);

  const selectedLabel = value
    ? statusConfig[value]?.label || value
    : "Tất cả trạng thái";

  useEffect(() => {
    function handleOutsideClick(event) {
      if (
        wrapperRef.current &&
        !wrapperRef.current.contains(event.target)
      ) {
        setIsOpen(false);
      }
    }

    document.addEventListener("mousedown", handleOutsideClick);

    return () => {
      document.removeEventListener("mousedown", handleOutsideClick);
    };
  }, []);

  function selectValue(nextValue) {
    onChange(nextValue);
    setIsOpen(false);
  }

  return (
    <div className="status-dropdown" ref={wrapperRef}>
      <button
        type="button"
        className="status-dropdown-trigger"
        onClick={() => setIsOpen((current) => !current)}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
      >
        {value ? (
          <StatusBadge status={value} compact />
        ) : (
          <span className="status-dropdown-all">
            {selectedLabel}
          </span>
        )}

        <span
          className={[
            "status-dropdown-arrow",
            isOpen ? "status-dropdown-arrow-open" : "",
          ].join(" ")}
        >
          ▾
        </span>
      </button>

      {isOpen && (
        <div
          className="status-dropdown-menu"
          role="listbox"
        >
          {includeAll && (
            <button
              type="button"
              className={[
                "status-dropdown-option",
                value === "" ? "status-dropdown-option-selected" : "",
              ].join(" ")}
              onClick={() => selectValue("")}
            >
              <span className="status-dropdown-all">
                Tất cả trạng thái
              </span>
            </button>
          )}

          {Object.keys(statusConfig).map((status) => (
            <button
              key={status}
              type="button"
              className={[
                "status-dropdown-option",
                value === status
                  ? "status-dropdown-option-selected"
                  : "",
              ].join(" ")}
              onClick={() => selectValue(status)}
            >
              <StatusBadge status={status} compact />

              {value === status && (
                <span className="status-dropdown-check">✓</span>
              )}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
