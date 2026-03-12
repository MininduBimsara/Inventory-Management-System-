"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { toErrorMessage } from "@/lib/format";
import { useCallback, useEffect, useState } from "react";

type Place = { id: number; name: string };
type Item = {
  id: number;
  name: string;
  code: string;
  quantity: number;
  status: string;
  place_id: number;
};

type CollectionResponse<T> = { data: T[] };

type QuantityForm = {
  itemId: number;
  amount: number;
  reason: string;
};

const statusActions = [
  { label: "Mark Damaged", path: "damaged", reason: "Damaged during handling" },
  {
    label: "Mark Missing",
    path: "missing",
    reason: "Missing during stock check",
  },
  {
    label: "Restore Damaged",
    path: "restore-damaged",
    reason: "Repaired and inspected",
  },
  {
    label: "Restore Missing",
    path: "restore-missing",
    reason: "Recovered and verified",
  },
];

export default function ItemsPage(): React.ReactElement {
  const [items, setItems] = useState<Item[]>([]);
  const [places, setPlaces] = useState<Place[]>([]);
  const [name, setName] = useState("Laptop Adapter");
  const [code, setCode] = useState("ITM-100");
  const [quantity, setQuantity] = useState(5);
  const [placeId, setPlaceId] = useState<number>(0);

  const [quantityForm, setQuantityForm] = useState<QuantityForm>({
    itemId: 0,
    amount: 1,
    reason: "Manual stock adjustment",
  });

  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadData = useCallback(async (): Promise<void> => {
    try {
      const [itemsResponse, placesResponse] = await Promise.all([
        apiRequest<CollectionResponse<Item>>("/items"),
        apiRequest<CollectionResponse<Place>>("/places"),
      ]);

      setItems(itemsResponse.data);
      setPlaces(placesResponse.data);

      if (placesResponse.data.length > 0 && placeId === 0) {
        setPlaceId(placesResponse.data[0].id);
      }

      if (itemsResponse.data.length > 0 && quantityForm.itemId === 0) {
        setQuantityForm((prev) => ({
          ...prev,
          itemId: itemsResponse.data[0].id,
        }));
      }
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  }, [placeId, quantityForm.itemId]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadData();
  }, [loadData]);

  const handleCreate = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await apiRequest("/items", {
        method: "POST",
        body: JSON.stringify({
          name,
          code,
          quantity,
          place_id: placeId,
          status: "available",
        }),
      });

      setMessage("Item created.");
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  const adjustQuantity = async (
    kind: "increase" | "decrease",
  ): Promise<void> => {
    try {
      setError(null);
      setMessage(null);
      await apiRequest(`/items/${quantityForm.itemId}/quantity/${kind}`, {
        method: "POST",
        body: JSON.stringify({
          amount: quantityForm.amount,
          reason: quantityForm.reason,
        }),
      });
      setMessage(`Quantity ${kind} successful.`);
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  const applyStatusAction = async (
    actionPath: string,
    reason: string,
  ): Promise<void> => {
    try {
      setError(null);
      setMessage(null);
      await apiRequest(`/items/${quantityForm.itemId}/status/${actionPath}`, {
        method: "POST",
        body: JSON.stringify({ reason }),
      });
      setMessage("Status update successful.");
      await loadData();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  return (
    <RequireAuth>
      <AppShell
        title="Item Management"
        subtitle="Create items and execute quantity and status mutations"
      >
        {message ? <p className="success-text">{message}</p> : null}
        {error ? <p className="error-text">{error}</p> : null}

        <form className="panel-card form-grid" onSubmit={handleCreate}>
          <h3>Create Item</h3>
          <input
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />
          <input
            value={code}
            onChange={(event) => setCode(event.target.value)}
            required
          />
          <input
            type="number"
            value={quantity}
            min={0}
            onChange={(event) => setQuantity(Number(event.target.value))}
            required
          />
          <select
            value={placeId}
            onChange={(event) => setPlaceId(Number(event.target.value))}
            required
          >
            {places.map((place) => (
              <option key={place.id} value={place.id}>
                {place.name}
              </option>
            ))}
          </select>
          <button type="submit">Create Item</button>
        </form>

        <div className="panel-card form-grid">
          <h3>Quantity and Status Actions</h3>
          <select
            value={quantityForm.itemId}
            onChange={(event) =>
              setQuantityForm((prev) => ({
                ...prev,
                itemId: Number(event.target.value),
              }))
            }
          >
            {items.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} ({item.code})
              </option>
            ))}
          </select>

          <input
            type="number"
            value={quantityForm.amount}
            min={1}
            onChange={(event) =>
              setQuantityForm((prev) => ({
                ...prev,
                amount: Number(event.target.value),
              }))
            }
          />

          <input
            value={quantityForm.reason}
            onChange={(event) =>
              setQuantityForm((prev) => ({
                ...prev,
                reason: event.target.value,
              }))
            }
          />

          <div className="button-row">
            <button
              type="button"
              onClick={() => void adjustQuantity("increase")}
            >
              Increase Quantity
            </button>
            <button
              type="button"
              onClick={() => void adjustQuantity("decrease")}
            >
              Decrease Quantity
            </button>
          </div>

          <div className="button-wrap">
            {statusActions.map((action) => (
              <button
                key={action.path}
                type="button"
                onClick={() =>
                  void applyStatusAction(action.path, action.reason)
                }
              >
                {action.label}
              </button>
            ))}
          </div>
        </div>

        <div className="panel-card">
          <h3>Inventory Items</h3>
          <table className="table-grid">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Qty</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.code}</td>
                  <td>{item.quantity}</td>
                  <td>{item.status}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </AppShell>
    </RequireAuth>
  );
}
