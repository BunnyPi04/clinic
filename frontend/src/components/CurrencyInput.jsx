function normalizeNumericValue(value) {
  return String(value ?? "").replace(/[^\d]/g, "");
}

function formatNumericValue(value) {
  const normalized = normalizeNumericValue(value);

  if (!normalized) {
    return "";
  }

  return new Intl.NumberFormat("en-US", {
    maximumFractionDigits: 0,
  }).format(Number(normalized));
}

export default function CurrencyInput({
  value,
  onChange,
  currency = "VNĐ",
  name,
  disabled = false,
  placeholder = "0",
}) {
  function handleChange(event) {
    const rawValue = normalizeNumericValue(event.target.value);

    onChange({
      target: {
        name,
        value: rawValue,
      },
    });
  }

  return (
    <div className="currency-input-wrapper">
      <input
        type="text"
        inputMode="numeric"
        name={name}
        value={formatNumericValue(value)}
        onChange={handleChange}
        disabled={disabled}
        placeholder={placeholder}
        autoComplete="off"
      />

      <span className="currency-input-suffix">
        {currency}
      </span>
    </div>
  );
}
